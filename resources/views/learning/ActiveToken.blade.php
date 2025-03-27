<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Renew Token</title>
</head>

<body style="display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f4;">
    <div>
        <h3>Enter Your Email To Regenerate Your Training Session</h3>
        <form id="renewTokenForm"
            style="background: white; padding: 99px; border-radius: 8px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); text-align: center;">
            <label for="email"
                style="display: block; font-size: 18px; font-weight: 600; font-family: system-ui; margin-bottom: 10px;">Email:</label>
            <input type="email" id="email" name="email" required
                style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px;">
            <button type="submit"
                style="background: #007BFF; color: white; padding: 10px 15px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
                Send
            </button>
            <p id="responseMessage" style="margin-top: 15px; font-size: 14px; color: green;"></p>
        </form>

    </div>


    <script>
        document.getElementById('renewTokenForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent form from refreshing the page

            const email = document.getElementById('email').value;

            fetch('/renew-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' // Laravel CSRF protection
                    },
                    body: JSON.stringify({
                        email: email
                    })
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('responseMessage').innerText = data.message;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('responseMessage').innerText = 'Error While Send Mail';
                    document.getElementById('responseMessage').style.color = 'red';
                });
        });
    </script>

</body>

</html>
