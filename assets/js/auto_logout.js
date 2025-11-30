(function() {
    let timeout = 1800 * 1000; 
    let logoutUrl = '/prof-it/auth/logout.php?timeout=1';
    let timer;

    function resetTimer() {
        clearTimeout(timer);
        timer = setTimeout(logout, timeout);
    }

    function logout() {
        window.location.href = logoutUrl;
    }

    function init(serverTimeout) {
        if (serverTimeout) {
            timeout = serverTimeout * 1000;
        }
        
        window.onload = resetTimer;
        document.onmousemove = resetTimer;
        document.onkeypress = resetTimer;
        document.onclick = resetTimer;
        document.onscroll = resetTimer;
        document.ontouchstart = resetTimer;

        resetTimer();
        console.log('Auto-logout initialized with timeout:', timeout / 1000, 'seconds');
    }

    window.initAutoLogout = init;
})();
