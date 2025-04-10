<div id="editGamified_training_form">
    <form action="{{auth('company')->check() ? route('update.gamified_training') : route('admin.update.gamified_training')}}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg-4">
                <div class="input-group mb-3">
                    <span class="input-group-text">{{ __('Module name') }}</span>
                    <input type="text" class="form-control required" name="module_name" placeholder="Enter a unique module name"
                        aria-label="Enter a unique module name" aria-describedby="basic-addon1" data-name="Module name"
                        required>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="input-group mb-3">
                    <span class="input-group-text">{{ __('Passing Score') }}</span>
                    <input type="number" class="form-control required mPassingScore" name="passing_score" min="0"
                        max="100" placeholder="70" aria-label="70" aria-describedby="basic-addon1"
                        data-name="Passing Score" required>
                    <span class="input-group-text">%</span>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="input-group mb-3">
                    <span class="input-group-text">{{ __('Category') }}</span>
                    <select class="form-select" name="category" id="edit_g_category">
                        <option value="international">{{ __('International') }}</option>
                        <option value="middle_east">{{ __('Middle East') }}</option>
                    </select>
                </div>
            </div>
    
        </div>
    
        <div class="row">
            <div class="col-lg-6">
    
                <div class="input-group">
                    <label class="input-group-text" for="edit_g_coverImageFile">{{ __('Cover Image') }}</label>
                    <input type="file" class="form-control" name="cover_file" id="edit_g_coverImageFile">
    
                </div>
                <small class="text-muted mx-3">{{ __('Only .jpeg, .jpg and .png files allowed') }}</small>
    
            </div>
            <div class="col-lg-6">
                <div class="input-group mb-3">
                    <span class="input-group-text">{{ __('Completion Time(minutes)') }}</span>
                    <input type="number" class="form-control required mCompTime" name="completion_time" placeholder="5"
                        aria-label="Username" aria-describedby="basic-addon1" data-name="Completion Time" required>
                </div>
            </div>
    
            <input type="hidden" id="gamifiedTrainingId" name="gamifiedTrainingId" value="">
            <input type="hidden" id="edit_gamifiedJsonData" name="gamifiedJsonData" value="">
            <input type="submit" id="editGamifiedTrainingSubmitBtn" value="Submit" class="d-none">
    
        </div>
    </form>
   
</div>
<hr>

<div>
    <div class="input-group mb-3">
        <span class="input-group-text">{{ __('Enter Video URL:') }}</span>
        <input type="text" class="form-control" id="edit_gamified_training_video_url" name="gamified_training_video_url"
            data-name="Gamified Training Video" required>
        <button class="btn btn-primary" type="button" id="fetchVideoUrl" onclick="fetchVideoUrl()">Fetch Video') }}</button>
    </div>
</div>

<div id="edit_game_training_video_preview" style="display: none;">
    <video id="editGameTrainingVideoPlayer" controls style="width: 100%;">
        <source src="https://sparrow.host/videos/financial-AR.mp4" type="video/mp4">
        {{ __('Your browser does not support the video tag.') }}
    </video>
</div>

<div id="edit_gamified_questions_container" style="display: none;">
    <div class="gamified_training_question border px-3 my-3">
        <div class="d-flex gap-2 justify-content-end my-3">
            <button type="button" class="btn btn-primary btn-sm btn-wave"
                onclick="addMoreGamifiedTrainingQues(this)">{{ __('Add More') }}</button>
            <button type="button" class="btn btn-danger deleteQuesBtn btn-sm btn-wave"
                onclick="deleteGamifiedTrainingQues(this)" style="display: none;">{{ __('Delete') }}</button>
        </div>

        <div class="input-group input-group-sm mb-3">
            <span class="input-group-text">{{ __('Enter Video Time:') }}</span>
            <input type="text" class="form-control time" name="video_time" onblur="isTimeValid(this)" data-name="Video Time" placeholder="0:32"
                required>
        </div>
        <div class="input-group input-group-sm mb-3">
            <span class="input-group-text">{{ __('Question:') }}</span>
            <input type="text" class="form-control question" name="question" data-name="Question" required>
        </div>
        <div class="input-group input-group-sm mb-3">
            <span class="input-group-text">{{ __('Option 1:') }}</span>
            <input type="text" class="form-control option1" name="options[]" required>
        </div>
        <div class="input-group input-group-sm mb-3">
            <span class="input-group-text">{{ __('Option 2:') }}</span>
            <input type="text" class="form-control option2" name="options[]" required>
        </div>
        <div class="input-group input-group-sm mb-3">
            <span class="input-group-text">{{ __('Option 3:') }}</span>
            <input type="text" class="form-control option3" name="options[]" required>
        </div>
        <div class="input-group input-group-sm mb-3">
            <span class="input-group-text">{{ __('Option 4:') }}</span>
            <input type="text" class="form-control option4" name="options[]" required>
        </div>
        <div class="input-group input-group-sm mb-3">
            <span class="input-group-text">{{ __('Correct Answer:') }}</span>
            <select class="form-select answer" name="correct_answer" required>
                <option value="0">{{ __('Option 1') }}</option>
                <option value="1">{{ __('Option 2') }}</option>
                <option value="2">{{ __('Option 3') }}</option>
                <option value="3">{{ __('Option 4') }}</option>
            </select>
        </div>
      
    </div>

</div>

<div class="d-flex justify-content-end mt-3">
    <button type="button" class="btn btn-primary" onclick="updateGamifiedQues()">
        {{ __('Update') }}
    </button>
</div>
