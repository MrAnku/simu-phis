<div>
    <div class="input-group mb-3">
        <span class="input-group-text" id="basic-addon1">Description</span>
        <input type="text" id="prompt" class="form-control" placeholder="Generate a invoice mail template of company">
        <button class="btn btn-primary" onclick="generateTemplate(this)" type="button" id="button-addon2">Generate
            Template</button>
    </div>

    <div>
        <textarea id="email-editor"></textarea>
    </div>
    <div class="d-flex justify-content-end mt-3">
        <button class="btn btn-primary" onclick="showAnotherModal(this)" type="button">Save template</button>

        <script>
            function showAnotherModal(button) {
                // Hide the current modal
                $('#generatePhishMailModal').modal('hide');

                // Show the another modal
                $('#savePhishMailModal').modal('show');

                // When the another modal is closed, show the current modal again
                $('#savePhishMailModal').on('hidden.bs.modal', function() {
                    $('#generatePhishMailModal').modal('show');
                });
            }
        </script>
    </div>
</div>
