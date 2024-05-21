function gotoTop() {
    document.body.scrollTop=0;
    document.documentElement.scrollTop=0;
};

function gotoEnd() {
    let scrollingElement = (document.scrollingElement || document.body);
    scrollingElement.scrollTop = scrollingElement.scrollHeight;
};