window.addEventListener('DOMContentLoaded', () => {
        Telegram.WebApp.ready();  // Important!

        const user = Telegram.WebApp.initDataUnsafe.user;
        fetch('/check_user.php',{
            method: 'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({chat_id:user.id})
        })
        .then(res =>res.json())
        .then(data=>{
            if(data.registered){
                document.getElementById("balance_amount").innerHTML=`${data.balance}ETB`

            }
            else{

            }

        })

    
});
