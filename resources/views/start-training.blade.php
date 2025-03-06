<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/simu-icon.png') }}">
    <title>Training Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 19px;
            line-height: 28px;
            font-family: system-ui;
        }

        label {
            display: block;
            text-align: left;
            font-size: 14px;
            margin: 8px 0 4px;
            font-weight: 600;
            color: #555;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            transition: 0.3s;
        }

        input:focus {
            border-color: #2575fc;
            outline: none;
            box-shadow: 0 0 5px rgba(37, 117, 252, 0.5);
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            font-family: system-ui;
        }

        button:hover {
            background: #1d5ed3;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>To start the training of <span style="color: #2575fc">{{ $training_module_name }}</span>, fill the details
            below</h2>

        <form id="trainingForm">
            <label for="name">Full Name</label>
            <input class="form-control" type="text" id="name" name="name" placeholder="Enter your full name"
                required>

            <label for="mobile">Mobile Number</label>
            <input class="form-control" type="tel" id="mobile" name="mobile"
                placeholder="Enter your mobile number" required>

            <button type="submit">Start Training</button>
        </form>

        <p id="responseMessage" style="color: green; display: none; font-weight: bold; margin-top: 10px;"></p>
    </div>

    <script>
        $(document).ready(function() {
            function getBase64IdFromURL() {
                let urlParts = window.location.pathname.split('/start-training/');
                return urlParts[urlParts.length - 1]; // Extract last segment
            }

            let base64Id = getBase64IdFromURL();
            console.log("base64Id", base64Id);

            $("#trainingForm").submit(function(event) {
                event.preventDefault();

                let formData = {
                    name: $("#name").val(),
                    phone_number: $("#mobile").val(),
                    encoded_id: base64Id,
                    _token: "{{ csrf_token() }}"
                };

                $.ajax({
                    type: "POST",
                    url: "{{ route('training.store') }}",
                    data: formData,
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            if (response.trainingUrl) {
                                window.location.href = response
                                .trainingUrl; // Redirect to training URL
                            } else {
                                $("#responseMessage").text(response.message).css("color",
                                    "green").show();
                                $("#trainingForm")[0].reset();
                            }
                        } else {
                            $("#responseMessage").text("Something went wrong!").css("color",
                                "red").show();
                        }
                    },
                    error: function(xhr) {
                        let errors = xhr.responseJSON.errors;
                        if (errors) {
                            let errorMsg = Object.values(errors).flat().join("\n");
                            $("#responseMessage").text(errorMsg).css("color", "red").show();
                        } else {
                            $("#responseMessage").text("An error occurred!").css("color", "red")
                                .show();
                        }
                    }
                });
            });
        });
    </script>

</body>

</html>
