const toggleSwitchTheme = document.querySelector('.theme-switch input[type="checkbox"]');
toggleSwitchTheme.addEventListener('change', switchTheme, false);

function switchTheme(e) {
    if (e.target.checked) {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
    }
    else {
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    }    
}

const thisTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : null;
if (thisTheme) {
    document.documentElement.setAttribute('data-theme', thisTheme);
    if (thisTheme === 'dark') {
        toggleSwitchTheme.checked = true;
    }
}
