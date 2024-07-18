<!DOCTYPE html>
<html>
<head>
    <title>Upload File</title>
</head>
<body>
    <form action="{{ route('upload.file') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <label for="file">Choose file:</label>
        <input type="file" name="file" id="file" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
