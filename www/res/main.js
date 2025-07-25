//   const tabs = document.querySelectorAll('.tab');
//   const contents = document.querySelectorAll('.tab-content');

//   tabs.forEach(tab => {
//     tab.addEventListener('click', () => {
//       const target = tab.getAttribute('data-tab');

//       // Снять активность со всех вкладок и контента
//       tabs.forEach(t => t.classList.remove('active'));
//       contents.forEach(c => c.classList.remove('active'));

//       // Активировать текущую
//       tab.classList.add('active');
//       document.getElementById(target).classList.add('active');
//     });
//   });

const tabs   = document.querySelectorAll('.tab');
const video  = document.getElementById('player');
const source = document.getElementById('videoSource');
// const files  = document.getElementById('files').querySelectorAll('.tab');
const files  = document.querySelectorAll('.file.tab');

tabs.forEach(tab => {
  tab.addEventListener('click', () => {
    const targets = tab.getAttribute('data-tabs');
    const target  = tab.getAttribute('data-tab');

    // console.log('targets',targets);
    // console.log('target',target);

    // Снять активность со всех вкладок
    tab.parentElement.querySelectorAll(':scope > .tab').forEach(t => t.classList.remove('active'));
    if(targets != null) document.getElementById(targets).querySelectorAll(':scope > .tab-content').forEach(t => t.classList.remove('active'));

    // Активировать текущую
    tab.classList.add('active');
    if(target != null) document.getElementById(target).classList.add('active');

    //Остановить player
    document.getElementById('current').querySelector('.empty').classList.remove('hidden');
    document.getElementById('current').querySelector('.label').textContent = null;
    if(targets != null) files.forEach(t => t.classList.remove('active'));
    source.src = null;
    video.load(); // Обновить плеер


    /*const target = tab.getAttribute('data-tab');
    const tabs2 = tab.parentElement.querySelectorAll(':scope > .tab');
    const contents2 = tab.parentElement.parentElement.querySelectorAll(':scope > .tab-content');

    console.log('tab',tab);
    console.log('target', target);
    console.log('tabs2', tabs2);
    console.log('contents2',contents2);

    // Снять активность со всех вкладок и контента
    tabs2.forEach(t => t.classList.remove('active'));
    contents2.forEach(c => c.classList.remove('active'));

    //Остановить player
    document.getElementById('current').querySelector('.empty').classList.remove('hidden');
    document.getElementById('current').querySelector('.label').textContent = null;
    files.forEach(t => t.classList.remove('active'));
    source.src = null;
    video.load(); // Обновить плеер

    // Активировать текущую
    tab.classList.add('active');
    if(target != null) document.getElementById(target).classList.add('active');
    */
  });
});


files.forEach(tab => {
  tab.addEventListener('click', () => {
    console.log('source.src',source.src);

    source.src = tab.getAttribute('data-file');
    video.load(); // Обновить плеер
    video.play(); // (опционально) начать воспроизведение

    document.getElementById('current').querySelector('.empty').classList.add('hidden');
    document.getElementById('current').querySelector('.label').textContent = tab.getAttribute('data-label');
  });
});
/*
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('camera-header');
  const rect = el.getBoundingClientRect();
  const height = window.innerHeight;

  console.log('DDDD ', height - rect.top);

  console.log('height:', height);
  console.log('X:', rect.left);
  console.log('Y:', rect.top);
  console.log('Ширина:', rect.width);
  console.log('Высота:', rect.height);

  // const el = document.querySelector('.my-element');
  // el.style.height = '200px';

  const tabs = document.querySelectorAll('.tabs');
  console.log('tabs',tabs);
  tabs.forEach(tab => {
    console.log('tab',tab);
    // console.log('-----',height - rect.top);
    // tab.style.heigh = height - rect.top;
    // tab.style.overflowY = 'auto';
  });
});
*/