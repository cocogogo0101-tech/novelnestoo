/* script.js — Theme toggling + maker label logic */
const App = (function(){
  const THEME_KEY = 'novelnest_theme_v1';
  const MAKER_LIGHT = 'Sun King';
  const MAKER_DARK = 'Dark King';

  function qs(s){ return document.querySelector(s); }
  function qsa(s){ return Array.from(document.querySelectorAll(s)); }

  function applyTheme(theme){
    if (theme === 'dark') document.documentElement.setAttribute('data-theme','dark');
    else document.documentElement.removeAttribute('data-theme');
    localStorage.setItem(THEME_KEY, theme);
    qsa('#theme-toggle').forEach(b => b.textContent = theme === 'dark' ? '🌙' : '☀️');
    updateMaker();
  }
  function toggleTheme(){
    const cur = localStorage.getItem(THEME_KEY) || 'light';
    applyTheme(cur === 'dark' ? 'light' : 'dark');
  }
  function updateMaker(){
    const theme = localStorage.getItem(THEME_KEY) || 'light';
    const maker = theme === 'dark' ? MAKER_DARK : MAKER_LIGHT;
    const el = qs('#maker-label');
    if (el) el.textContent = maker;
  }

  function init(){
    const theme = localStorage.getItem(THEME_KEY) || 'light';
    applyTheme(theme);
    const btns = qsa('#theme-toggle');
    btns.forEach(b => b.addEventListener('click', toggleTheme));
    updateMaker();
  }

  return { init };
})();