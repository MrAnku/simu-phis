<form action="{{ route('trainingmodule.add') }}" id="newModuleForm" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">Module name</span>
                                    <input type="text" class="form-control" name="moduleName"
                                        placeholder="Enter a unique module name" aria-label="Enter a unique module name"
                                        aria-describedby="basic-addon1" data-name="Module name" required>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">Passing Score</span>
                                    <input type="number" class="form-control mPassingScore" name="mPassingScore"
                                        min="0" max="100" placeholder="70" aria-label="70"
                                        aria-describedby="basic-addon1" data-name="Passing Score" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">Category</span>
                                    <select class="form-select" name="category" id="category">
                                        <option value="international">International</option>
                                        <option value="middle_east">Middle East</option>
                                    </select>
                                </div>
                            </div>
                           
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                              
                                <div class="input-group">
                                    <label class="input-group-text" for="coverImageFile">Cover Image</label>
                                    <input type="file" class="form-control" name="mCoverFile" id="coverImageFile">
                                    
                                </div>
                                <small class="text-muted mx-3">Only .jpeg, .jpg and .png files allowed</small>
                                
                            </div>
                            <div class="col-lg-6">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">Completion Time(minutes)</span>
                                    <input type="number" class="form-control mCompTime" name="mCompTime"
                                        placeholder="5" aria-label="Username" aria-describedby="basic-addon1"
                                        data-name="Completion Time" required>
                                </div>
                            </div>

                        </div>
                        <input type="hidden" name="jsonData" id="jsonDataInput" value="">
                        <input type="submit" id="addTrainingsubmitButton" value="Submit" class="d-none">

                    </form>

                    <hr>

                    <div class="questionSection">
                        <div class="my-2">
                            <button type="button" class="btn btn-primary mx-1" onclick="createPageForm('forms')">
                                Add Question
                            </button>
                            <button type="button" onclick="createStatementPageForm('forms')"
                                class="btn btn-secondary mx-1">
                                Add Statement
                            </button>
                        </div>

                        <div id="forms"></div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-primary" onclick="checkRequiredInputs()">
                            Submit
                        </button>
                    </div>