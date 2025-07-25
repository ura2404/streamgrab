#!/bin/bash
set -euo pipefail

# =========================================
# 🎥 Скрипт записи RTSP-потока с камеры
#
# Аргументы запуска:
#   $1 — путь к global.conf (глобальные параметры)
#   $2 — путь к camera.conf (камера: имя и URL)
# =========================================

# === Проверка окружения ===
if ! command -v apache2 >/dev/null 2>&1; then
  echo "❌ Не установлен apache2"
  exit 1
fi
if ! command -v ffmpeg >/dev/null 2>&1; then
  echo "❌ Не установлен ffmpeg"
  exit 1
fi 

# if ! php -m | grep -q '^intl$'; then
#   echo "❌ Не установлен php-intl"
#   exit 1
# fi 

# === Проверка аргументов ===
GLOBAL_CONF="${1:-}"
CAMERA_CONF="${2:-}"

if [[ -z "$GLOBAL_CONF" || -z "$CAMERA_CONF" || ! -f "$GLOBAL_CONF" || ! -f "$CAMERA_CONF" ]]; then
  echo "❌ Использование: $0 /path/to/global.conf /path/to/camera.conf"
  exit 1
fi

# === Загрузка конфигураций ===
source "$GLOBAL_CONF"
source "$CAMERA_CONF"

# === Проверка обязательных параметров ===
: "${HEAP_DIR:?}"
: "${CAMERA_NAME:?}"
: "${STREAM_URL:?}"
: "${FFMPEG_PATH:?}"
: "${SCALE_RESOLUTION:?}"
: "${SEGMENT_DURATION:?}"
: "${RETENTION_DAYS:?}"
: "${MAX_FOLDER_SIZE_GB:?}"
: "${RESTART_INTERVAL:?}"
: "${PING_RETRY_INTERVAL:?}"
: "${MAX_LOG_SIZE_MB:?}"
: "${MAX_FILE_AGE_SEC:?}"

# === Пути ===
BASE_DIR="$HEAP_DIR/$CAMERA_NAME"
OUTPUT_DIR="$BASE_DIR/streams"
LOG_DIR="$BASE_DIR/logs"
LOG_FILE="$LOG_DIR/${CAMERA_NAME}_log.txt"

mkdir -p "$OUTPUT_DIR" "$LOG_DIR"
touch "$LOG_FILE"

# === Очистка лога, если слишком большой ===
truncate_log_if_too_big() {
  if [[ -f "$LOG_FILE" ]]; then
    local size_bytes
    size_bytes=$(stat -c %s "$LOG_FILE")
    local max_bytes=$((MAX_LOG_SIZE_MB * 1024 * 1024))
    if (( size_bytes > max_bytes )); then
      # echo "[$(date '+%Y-%m-%d %H:%M:%S')] ⚠️ Очистка лога: > $MAX_LOG_SIZE_MB MB" > "$LOG_FILE"
      tail -n 1000 "$LOG_FILE" > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
      echo "[$(date '+%Y-%m-%d %H:%M:%S')] ⚠️ Лог усечён до 1000 строк" >> "$LOG_FILE"
    fi
  fi
}

# === Логирование ===
log() {
  truncate_log_if_too_big
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# === Очистка старых файлов по дате ===
cleanup_old_files() {
  find "$OUTPUT_DIR" -type f -name "*.mkv" -mtime +$RETENTION_DAYS -print -delete | while read -r file; do
    log "🗑️ Удалён по дате: $file"
  done
}

# === Очистка по объёму ===
enforce_folder_size_limit() {
  local size_bytes
  size_bytes=$(du -sb "$OUTPUT_DIR" | cut -f1)
  local size_gb=$((size_bytes / 1024 / 1024 / 1024))

  if (( size_gb >= MAX_FOLDER_SIZE_GB )); then
    log "⚠️ Папка > $size_gb ГБ. Удаление старых файлов..."
    find "$OUTPUT_DIR" -type f -name "*.mkv" -printf "%T@ %p\n" | sort -n | cut -d' ' -f2- | while read -r file; do
      rm -f "$file"
      log "🗑️ Удалён по объёму: $file"
      size_bytes=$(du -sb "$OUTPUT_DIR" | cut -f1)
      size_gb=$((size_bytes / 1024 / 1024 / 1024))
      if (( size_gb < MAX_FOLDER_SIZE_GB )); then
        log "✅ Объём после очистки: $size_gb ГБ"
        break
      fi
    done
  fi
}

# === Проверка доступности камеры по ping ===
check_camera_ping() {
  local ip
  ip=$(echo "$STREAM_URL" | sed -E 's#rtsp://([^/@]+@)?([^:/]+).*#\2#')
  ping -c 2 -W 2 "$ip" > /dev/null 2>&1
}

# === Главный цикл ===
record_loop() {

    LAST_DATE=$(date +%F)

    while true; do
      cleanup_old_files
      enforce_folder_size_limit

      until check_camera_ping; do
        log "❌ Камера недоступна (ping). Повтор через $PING_RETRY_INTERVAL сек..."
        sleep "$PING_RETRY_INTERVAL"
      done

      log "✅ Камера доступна. Запуск FFmpeg..."

      $FFMPEG_PATH -loglevel error -i "$STREAM_URL" \
        -vf scale="$SCALE_RESOLUTION" \
        -c:v libx264 -preset veryfast -crf 23 \
        -c:a aac -b:a 128k \
        -movflags +faststart \
        -f segment \
        -segment_format mkv \
        -segment_time "$SEGMENT_DURATION" \
        -reset_timestamps 1 -strftime 1 \
        "$OUTPUT_DIR/stream_%Y-%m-%d_%H-%M-%S.mkv" >> "$LOG_FILE" 2>&1 &

#            -vf "fps=1" \

      FFMPEG_PID=$!
      log "🎥 FFmpeg запущен (PID: $FFMPEG_PID)"
      START_TIME=$(date +%s)

      while true; do
        sleep 10
        CURRENT_TIME=$(date +%s)
        ELAPSED=$((CURRENT_TIME - START_TIME))

        # Проверка на смену суток (перезапуск в полночь)
        CURRENT_DATE=$(date +%F)
        if [[ "$CURRENT_DATE" != "$LAST_DATE" ]]; then
          log "🕛 Полночь. Перезапуск FFmpeg для новой даты."
          kill "$FFMPEG_PID" 2>/dev/null || kill -9 "$FFMPEG_PID" 2>/dev/null
          wait "$FFMPEG_PID" 2>/dev/null || true
          LAST_DATE="$CURRENT_DATE"
          break
        fi

        if ! ps -p "$FFMPEG_PID" > /dev/null; then
          log "⚠️ FFmpeg завершился досрочно (работал $ELAPSED сек)"
          break
        fi

        LAST_FILE=$(find "$OUTPUT_DIR" -type f -name "*.mkv" -printf "%T@ %p\n" 2>/dev/null | sort -n | tail -n 1 | cut -d' ' -f2- || true)
        if [[ -n "$LAST_FILE" ]]; then
          LAST_TIME=$(stat -c %Y "$LAST_FILE")
          NOW=$(date +%s)
          AGE=$((NOW - LAST_TIME))
          if (( AGE > MAX_FILE_AGE_SEC )); then
            log "🛑 FFmpeg завис (нет новых файлов > $MAX_FILE_AGE_SEC сек). Перезапуск..."
            kill -9 "$FFMPEG_PID"
            break
          fi
        fi

        if (( RESTART_INTERVAL != 0 )); then
          if (( ELAPSED >= RESTART_INTERVAL )); then
            # kill "$FFMPEG_PID" || kill -9 "$FFMPEG_PID"
            kill "$FFMPEG_PID" 2>/dev/null || kill -9 "$FFMPEG_PID" 2>/dev/null
            wait "$FFMPEG_PID" 2>/dev/null || true
            log "🔁 Плановый перезапуск FFmpeg"
            break
          fi
        fi
      done

      sleep 2
    done
}

record_loop
