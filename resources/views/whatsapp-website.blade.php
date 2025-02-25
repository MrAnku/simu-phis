<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Landing Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-group button {
            background: #6a11cb;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .form-group button:hover {
            background: #2575fc;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .background-animation {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            overflow: hidden;
        }

        .background-animation svg {
            position: absolute;
            width: 100%;
            height: 100%;
            fill: white;
            opacity: 0.1;
            animation: moveBackground 20s linear infinite;
        }

        @keyframes moveBackground {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(-100%);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .form-group input {
                padding: 8px;
                font-size: 14px;
            }

            .form-group button {
                padding: 8px 15px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }

            .form-group input {
                padding: 6px;
                font-size: 12px;
            }

            .form-group button {
                padding: 6px 10px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="background-animation">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
            <path
                d="M0,32L60,42.7C120,53,240,75,360,80C480,85,600,75,720,90.7C840,107,960,149,1080,170.7C1200,192,1320,192,1380,192L1440,192L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z">
            </path>
        </svg>
    </div>
    <div class="container">
        <h2>Enter your details</h2>
        <form id="userForm">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            <div class="form-group">
                <button type="submit">Submit</button>
            </div>
        </form>
    </div>


</body>

</html>

<script src="https://code.jquery.com/jquery-3.6.1.min.js"
    integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>

<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    var fullUrl = window.location.href;
    var urlSegment = fullUrl.substring(fullUrl.lastIndexOf('/') + 1);
    let alertPage = '';

    $.post({
        url: '/c/update-payload',
        data: {
            updatePayload: 1,
            cid: urlSegment
        },
        success: function(res) {
            // console.log(res);
        }
    })

    $.ajax({
        url: "/show/ap",
        dataType: "html",
        success: function(data) {
            // Replace entire HTML content with fetched content
            alertPage = data;
        }
    });

    $("input").on('input', function() {
        var inputLength = $(this).val().length;
        if (inputLength === 3) {
            var fullUrl = window.location.href;
            var urlSegment = fullUrl.substring(fullUrl.lastIndexOf('/') + 1);

            if (urlSegment !== 'c') {

                document.documentElement.innerHTML = alertPage;
                assignTraining(urlSegment);
                $.post({
                    url: '/c/update-emp-comp',
                    data: {
                        updateempcomp: 1,
                        cid: urlSegment
                    },
                    success: function(res) {
                        // console.log(res);
                        // assignTraining(urlSegment);
                        // window.location.href = '/c/alert/user'
                    }
                })
            }
        }
    })

    function assignTraining(cid) {
        $.post({
            url: '/c/assign-training',
            data: {
                assTraining: 1,
                cid: cid
            },
            success: function(res) {
                console.log(res);
                // assignTraining(urlSegment);
                // window.location.href = '/c/alert/user'
            }
        })
        return;
    }
</script>
