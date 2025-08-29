const user= Telegram.WebApp.initDataUnsafe.user;
document.getElementById('data').innerHTML=`<pre>${JSON.stringify(user,null,2)}</pre>`