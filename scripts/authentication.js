window.addEventListener('DOMContentLoaded', () => {
    if (window.Telegram && Telegram.WebApp) {
        Telegram.WebApp.ready();  // Important!

        const user = Telegram.WebApp.initDataUnsafe.user;

        if (user) {
            document.getElementById('data').innerHTML = `<pre>${JSON.stringify(user, null, 2)}</pre>`;
        } else {
            document.getElementById('data').innerHTML = 'User data not available.';
        }
    } else {
        document.getElementById('data').innerHTML = 'Please open this page inside the Telegram app.';
    }
});
