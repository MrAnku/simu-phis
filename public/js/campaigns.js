function checkResponse(res) {
    if (res.status == 1) {
        Swal.fire(
            res.msg,
            '',
            'success'
        ).then(function () {
            window.location.href = window.location.href
        })
    } else {
        Swal.fire(
            res.msg,
            '',
            'error'
        ).then(function () {
            window.location.href = window.location.href
        })
    }
}


function relaunch_camp(campid) {
    Swal.fire({
        title: 'Are you sure?',
        text: "The previous statistics and reports of this campaign will be erased.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e6533c',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Re-Launch'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post({
                url: '/campaigns/relaunch',
                data: {
                    campid: campid
                },
                success: function (res) {

                    // console.log(res)
                    checkResponse(res);
                }
            })
        }
    })


}

function reschedulecampid(id) {
    $("#recampid").val(id);
}

function deletecampaign(campid) {

    Swal.fire({
        title: 'Are you sure?',
        text: "Are you sure that you want to delete this Campaign?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e6533c',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Delete'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post({
                url: '/campaigns/delete',
                data: {
                    campid: campid
                },
                success: function (res) {

                    // console.log(res)
                    window.location.href = window.location.href;
                }
            })
        }
    })


}


var selectedPhishingMaterial = [];

function selectPhishingMaterial(checkbox) {
    var label = document.querySelector(`label[for="${checkbox.id}"]`);

    if (checkbox.checked) {
        // Add value to the checkedValues array
        selectedPhishingMaterial.push(checkbox.value);

        // Change the text inside the label
        label.textContent = "Attack selected";

        // Add the classes to the label
        label.classList.add('bg-primary', 'text-white');
    } else {
        // Remove value from the checkedValues array
        selectedPhishingMaterial = selectedPhishingMaterial.filter(value => value !== checkbox.value);

        // Change the text back to the original
        label.textContent = "Select this attack";

        // Remove the classes from the label
        label.classList.remove('bg-primary', 'text-white');

        checkbox.blur();
    }

    // Log the updated checkedValues array
    console.log(selectedPhishingMaterial);
}


var selectedTrainings = [];

function selectTrainingModule(checkbox) {
    var label = document.querySelector(`label[for="${checkbox.id}"]`);

    if (checkbox.checked) {
        // Add value to the checkedValues array
        selectedTrainings.push(checkbox.value);

        // Change the text inside the label
        label.textContent = "Training selected";

        // Add the classes to the label
        label.classList.add('bg-primary', 'text-white');
    } else {
        // Remove value from the checkedValues array
        selectedTrainings = selectedTrainings.filter(value => value !== checkbox.value);

        // Change the text back to the original
        label.textContent = "Select this training";

        // Remove the classes from the label
        label.classList.remove('bg-primary', 'text-white');

        checkbox.blur();
    }

    // Log the updated checkedValues array
    console.log(selectedTrainings);
}




//campaign type toggling
$("#campaign_type").on('change', function () {

    var type = $(this).val();
    if (type == 'Phishing') {

        $("#pm_step").show()
        $("#tm_step").hide()

        $("#pm_step_form").addClass('included');
        $("#pm_step_form input[type='radio']").addClass('required');

        $("#tm_step_form").removeClass('included');
        $("#tm_step_form input[type='radio']").removeClass('required');

    } else if (type == 'Training') {

        $("#pm_step").hide()
        $("#tm_step").show()

        $("#pm_step_form").removeClass('included');

        $("#tm_step_form").addClass('included');

    } else if (type == 'Phishing & Training') {

        $("#pm_step").show()
        $("#tm_step").show()

        $("#pm_step_form").addClass('included');
        $("#tm_step_form").addClass('included');

    }
})



$(document).ready(function () {

    var current_fs, next_fs, previous_fs; //fieldsets
    var opacity;
    var days_until_due = '';
    $(".next").click(function () {

        current_fs = $(this).parent();
        next_fs = $(this).parent().next();

        if (!next_fs.hasClass('included')) {
            next_fs = next_fs.next();
        }

        var allFilled = true;
        current_fs.find('.required').each(function () {
            if ($(this).val() === '') {
                allFilled = false;
                return false; // Exit the loop early if any field is empty
            }

        });

        if (current_fs.attr('id') === 'pm_step_form') {
            // Check if at least one checkbox button is checked
            days_until_due = '';
            var radioChecked = false;
            current_fs.find('input[type="checkbox"][name="phish_material"]').each(function () {
                if ($(this).is(':checked')) {
                    radioChecked = true;
                    return false; // Exit the loop early if any radio button is checked
                }
            });

            if (!radioChecked) {
                alert('Please select phishing material');
                return; // Stop further execution
            }
        }

        if (current_fs.attr('id') === 'tm_step_form') {
            // Check if at least one checkbox button is checked
            var radioChecked = false;
            current_fs.find('input[type="checkbox"][name="training_module"]').each(function () {
                if ($(this).is(':checked')) {
                    radioChecked = true;
                    return false; // Exit the loop early if any radio button is checked
                }
            });

            days_until_due = $('#days_until_due').val();
            if (days_until_due == '') {
                alert('Please enter days until due');
                return false; // Stop further execution
            }
            if (days_until_due < 1) {
                alert('Days until due must be greater than 0');
                return false; // Stop further execution
            }

            if (!radioChecked) {
                alert('Please select training module');
                return; // Stop further execution
            }
        }




        if (allFilled) {
            //Add Class Active
            $("#progressbar li").eq($("fieldset").index(next_fs)).addClass("active");

            //show the next fieldset
            next_fs.show();
            //hide the current fieldset with style
            current_fs.animate({
                opacity: 0
            }, {
                step: function (now) {
                    // for making fielset appear animation
                    opacity = 1 - now;

                    current_fs.css({
                        'display': 'none',
                        'position': 'relative'
                    });
                    next_fs.css({
                        'opacity': opacity
                    });
                },
                duration: 600
            });

            if ($(this).hasClass('last-step')) {
                // The targeted element has class "last-step"
                reviewFormData();
            }
        } else {
            // Alert or inform the user that some required fields are empty
            alert('Please fill all required fields!');
        }




    });

    $(".previous").click(function () {

        current_fs = $(this).parent();
        previous_fs = $(this).parent().prev();

        if (!previous_fs.hasClass('included')) {
            previous_fs = previous_fs.prev();
        }

        //Remove class active
        $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("active");

        //show the previous fieldset
        previous_fs.show();

        //hide the current fieldset with style
        current_fs.animate({
            opacity: 0
        }, {
            step: function (now) {
                // for making fielset appear animation
                opacity = 1 - now;

                current_fs.css({
                    'display': 'none',
                    'position': 'relative'
                });
                previous_fs.css({
                    'opacity': opacity
                });
            },
            duration: 600
        });
    });

    $('.radio-group .radio').click(function () {
        $(this).parent().find('.radio').removeClass('selected');
        $(this).addClass('selected');
    });

    $(".submit").click(function () {
        return false;
    })

    var dataToBeSaved = {};
    function reviewFormData() {

        function launch_time() {
            var currentDate = new Date();
            var formattedDate = formatDate(currentDate);
            return formattedDate;
        }
        var training_module = '';
        var trainingLang = '';
        var trainingType = '';
        var phishing_email = '';
        var phishing_lang = '';
        var training_assign = '';

        if (campaign_type.value == "Phishing") {
            phishing_email = selectedPhishingMaterial;
            phishing_lang = email_lang.value;
        }

        if (campaign_type.value == "Training") {
            training_module = selectedTrainings
            trainingLang = training_lang.value;
            trainingType = training_type.value;
            training_assign = training_assignment.value;
        }
        if (campaign_type.value == "Phishing & Training") {
            phishing_email = selectedPhishingMaterial;
            phishing_lang = email_lang.value;
            training_module = selectedTrainings;
            trainingLang = training_lang.value;
            trainingType = training_type.value;
            training_assign = training_assignment.value;
        }
        var formData = {

            camp_name: camp_name.value,
            campaign_type: campaign_type.value,
            users_group: users_group.value,
            email_lang: phishing_lang,
            phish_material: phishing_email,
            trainingLang: trainingLang,
            training_mod: training_module,
            training_type: trainingType,
            training_assignment: training_assign,
            schType: $("input[name='schType']:checked").val(),
            schBetRange: schBetRange.value,
            schTimeStart: schTimeStart.value,
            schTimeEnd: schTimeEnd.value,
            schTimeZone: schTimeZone.value,
            emailFreq: $("input[name='emailFreq']:checked").val(),
            expire_after: expire_after.value,
            days_until_due: days_until_due,

            launch_time: launch_time()

        };

        revCampName.value = formData.camp_name ?? '--';
        revCampType.value = $("#campaign_type option:selected").text().trim() ?? '--';
        revEmpGroup.value = $("#users_group option:selected").text().trim() ?? '--';
        revEmailLang.value = $("#email_lang option:selected").text() ?? '--';
        // revPhishmat.value = selectedPhishingMaterial.join(', ') ?? '--';
        revTrainingLang.value = $("#training_lang option:selected").text() ?? '--';

        var selectedTrainingModNames = [];
        var selectedPhishMatNames = [];

        $('input[name="training_module"]:checked').each(function () {
            var trainingModName = $(this).data('trainingname');
            if (trainingModName) {
                selectedTrainingModNames.push(trainingModName);
            }
        });

        revTrainingMod.value = selectedTrainingModNames.join(', ') ?? '--';



        $('input[name="phish_material"]:checked').each(function () {
            var phishMatName = $(this).data('phishmatname');
            if (phishMatName) {
                selectedPhishMatNames.push(phishMatName);
            }
        });

        revPhishmat.value = selectedPhishMatNames.join(', ') ?? '--';

        revTrainingType.value = $("#training_type option:selected").text().trim() ?? '--';
        revCampDelivery.value = $("input[name='schType']:checked").data('val') ?? '--';
        revBtwDays.value = formData.schBetRange ?? '--';
        revSchTimeStart.value = formData.schTimeStart ?? '--';
        revSchTimeEnd.value = formData.schTimeEnd ?? '--';
        revSchTimeZone.value = formData.schTimeZone ?? '--';
        revEmailFreq.value = $("input[name='emailFreq']:checked").data('val') ?? '--';
        revExpAfter.value = formData.expire_after ?? '--';
        revDays_until_due.value = formData.days_until_due ?? '--';

        if (formData.campaign_type == 'Phishing') {
            $("#revTrainingLang").parent().parent().hide();
            $("#revTrainingMod").parent().parent().hide();
            $("#revTrainingType").parent().parent().hide();

            $("#revPhishmat").parent().parent().show();
            $("#revEmailLang").parent().parent().show();
            $("#revDays_until_due").parent().parent().hide();
        }

        if (formData.campaign_type == 'Training') {
            $("#revPhishmat").parent().parent().hide();
            $("#revEmailLang").parent().parent().hide();

            $("#revTrainingLang").parent().parent().show();
            $("#revTrainingMod").parent().parent().show();
            $("#revTrainingType").parent().parent().show();
            $("#revDays_until_due").parent().parent().show();
        }

        if (formData.schType == 'immediately') {
            $("#revBtwDays").parent().parent().hide();
            $("#revSchTimeZone").parent().parent().hide();
            $("#revBtwTime").hide();


        }

        if (formData.schType == 'scheduled') {
            $("#revBtwDays").parent().parent().show();
            $("#revSchTimeZone").parent().parent().show();
            $("#revBtwTime").show();


        }
        if (formData.expire_after == "") {
            $("#revExpAfter").parent().parent().hide();
        } else {
            $("#revExpAfter").parent().parent().show();
        }

        // Output JSON
        console.log(formData);

        dataToBeSaved = formData;
    }



    $('#createCampaign').click(function (e) {
        e.preventDefault();

        console.log(dataToBeSaved);
        $.post({
            url: '/campaigns/create',
            data: dataToBeSaved,
            success: function (res) {
                checkResponse(res);
                // console.log(res);
            }
        })
    })



});

function formatDate(date) {
    var month = (date.getMonth() + 1).toString().padStart(2, '0'); // Get month with leading zero
    var day = date.getDate().toString().padStart(2, '0'); // Get day with leading zero
    var year = date.getFullYear(); // Get full year
    var hours = date.getHours().toString().padStart(2, '0'); // Get hours with leading zero
    var minutes = date.getMinutes().toString().padStart(2, '0'); // Get minutes with leading zero
    return month + '/' + day + '/' + year + ' ' + hours + ':' + minutes;
}



//handling imediate and schedule btn
$("#imediateLabelBtn").click(function () {
    $("#dvSchedule2").addClass("d-none");
    $("#email_frequency").removeClass("d-none");
    // var currentDate = new Date();
    // var formattedDate = formatDate(currentDate);
    // $("#launch_time").val(formattedDate);
})
$("#scheduleLabelBtn").click(function () {
    $("#dvSchedule2").removeClass("d-none");
    $("#email_frequency").removeClass("d-none");
})

$("#scheduleLLabelBtn").click(function () {
    $("#dvSchedule2").addClass("d-none");
    $("#email_frequency").addClass("d-none")
})

$('label[for="foneoff"]').click(function () {
    $("#exp_after").addClass("d-none");
})

$('label[for="fmonthly"]').click(function () {
    $("#exp_after").removeClass("d-none");
})
$('label[for="fweekly"]').click(function () {
    $("#exp_after").removeClass("d-none");
})
$('label[for="fquaterly"]').click(function () {
    $("#exp_after").removeClass("d-none");
})

//handling imediate and schedule btn
$("#rimediateLabelBtn").click(function () {
    $("#rdvSchedule2").addClass("d-none");
    $("#remail_frequency").removeClass("d-none");
    // var currentDate = new Date();
    // var formattedDate = formatDate(currentDate);
    // $("#launch_time").val(formattedDate);
})
$("#rscheduleLabelBtn").click(function () {
    $("#rdvSchedule2").removeClass("d-none");
    $("#remail_frequency").removeClass("d-none");
})

$("#rscheduleLLabelBtn").click(function () {
    $("#rdvSchedule2").addClass("d-none");
    $("#remail_frequency").addClass("d-none")
})

$('label[for="rfoneoff"]').click(function () {
    $("#rexp_after").addClass("d-none");
})

$('label[for="rfmonthly"]').click(function () {
    $("#rexp_after").removeClass("d-none");
})
$('label[for="rfweekly"]').click(function () {
    $("#rexp_after").removeClass("d-none");
})
$('label[for="rfquaterly"]').click(function () {
    $("#rexp_after").removeClass("d-none");
})





// Event listener for input field change
$('#templateSearch').on('input', function () {
    var searchValue = $(this).val().toLowerCase(); // Get the search value and convert it to lowercase

    clearTimeout($.data(this, 'timer'));
    if (searchValue.length > 2) {
        var wait = setTimeout(function () {
            // Call the search function here
            searchPhishingMaterial(searchValue);
        }, 2000);
        $(this).data('timer', wait);
    } else {
        if (phishing_materials_before_search !== '') {
            $('#phishingEmailsCampModal').html(phishing_materials_before_search)
            phishing_materials_before_search = '';
        }
    }
});

let phishing_materials_before_search = ''

function searchPhishingMaterial(searchValue) {
    $('#phishEmailSearchSpinner').show();
    phishing_materials_before_search = $('#phishingEmailsCampModal').html();
    // Loop through each template card
    $.post({
        url: '/campaigns/search-phishing-material',
        data: { search: searchValue },
        success: function (res) {
            if (res.status === 1) {
                // Clear existing results
                $('#phishEmailSearchSpinner').hide();
                $('#phishingEmailsCampModal').empty()
                // Append new results
                const htmlrows = prepareHtml(res.data);
                $('#phishingEmailsCampModal').append(htmlrows);
            } else {
                Swal.fire(res.msg, '', 'error');
            }
        }
    });
}



function resendTrainingAssignmentReminder(btn, email, training) {

    console.log(email, training);

    Swal.fire({
        title: 'Are you sure?',
        text: "This will send a training reminder to: " + email,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e6533c',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, send the reminder email'
    }).then((result) => {
        if (result.isConfirmed) {
            var icon = $(btn).html();
            $(btn).html(`<div class="spinner-border spinner-border-sm me-4" role="status">
    <span class="visually-hidden">Loading...</span>
</div>`);
            $.post({
                url: '/campaigns/send-training-reminder',
                data: {
                    email: email,
                    training: training
                },
                success: function (res) {

                    // console.log(res)
                    checkResponse(res);
                    $(btn).html(icon);
                }
            })

            // console.log('sending reminder email');
        }
    })
}

function completeAssignedTraining(btn, encodedTrainingId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will mark the training as completed",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e6533c',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, mark as completed'
    }).then((result) => {
        if (result.isConfirmed) {
            var icon = $(btn).html();
            $(btn).html(`<div class="spinner-border spinner-border-sm me-4" role="status"><span class="visually-hidden">Loading...</span></div>`);

            $.post({
                url: '/campaigns/complete-training',
                data: {
                    encodedTrainingId: encodedTrainingId
                },
                success: function (res) {

                    // console.log(res)
                    checkResponse(res);
                    $(btn).html(icon);
                }
            })

            // console.log('sending reminder email');
        }
    })


}

function removeAssignedTraining(btn, encodedTrainingId, trainingname, email) {
    Swal.fire({
        title: 'Are you sure?',
        text: `This will remove the ${trainingname} training assigned to: ${email}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e6533c',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, remove assignment'
    }).then((result) => {
        if (result.isConfirmed) {
            var icon = $(btn).html();
            $(btn).html(`<div class="spinner-border spinner-border-sm me-4" role="status"><span class="visually-hidden">Loading...</span></div>`);

            $.post({
                url: '/campaigns/remove-training',
                data: {
                    encodedTrainingId: encodedTrainingId
                },
                success: function (res) {

                    // console.log(res)
                    checkResponse(res);
                    $(btn).html(icon);
                }
            })

            // console.log('sending reminder email');
        }
    })


}

let phishing_emails_page = 2;
function loadMorePhishingEmails(btn) {
    btn.disabled = true;
    btn.innerText = 'Loading...'
    $.post({
        url: '/campaigns/show-more-phishing-emails',
        data: {
            page: phishing_emails_page
        },
        success: function (res) {
            // console.log(res)
            if (res.status !== 1) {
                Swal.fire(
                    res.msg,
                    '',
                    'error'
                )
                return;
            }
            if (res.data.length === 0) {

                btn.disabled = true;
                btn.innerText = 'No more phishing materials';
                return;
            }
            const htmlrows = prepareHtml(res.data);
            $('#phishingEmailsCampModal').append(htmlrows);
            btn.disabled = false;
            btn.innerText = 'Show More';
            phishing_emails_page++;
        }
    })
}

let training_page = 2;
function loadMoreTrainings(btn) {
    const category = $('#training_cat').val();
    btn.disabled = true;
    btn.innerText = 'Loading...'
    $.post({
        url: '/campaigns/show-more-trainings',
        data: {
            page: training_page,
            category: category
        },
        success: function (res) {
            // console.log(res)
            if (res.status !== 1) {
                Swal.fire(
                    res.msg,
                    '',
                    'error'
                )
                return;
            }
            if (res.data.length === 0) {

                btn.disabled = true;
                btn.innerText = 'No more training modules';
                return;
            }
            const htmlrows = prepareTrainingHtml(res.data);
            $('#trainingModulesCampModal').append(htmlrows);
            btn.disabled = false;
            btn.innerText = 'Show More';
            training_page++;
        }
    })
}

function prepareHtml(data) {
    let html = '';
    data.forEach(email => {

        html += `<div class="col-lg-6 email_templates">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center w-100">
                            <div class="">
                                <div class="fs-15 fw-semibold">${email.name}</div>
                                    ${email.company_id == 'default' ? '(Default)' : ''}</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body htmlPhishingGrid" style="background: white;">
                        <iframe class="phishing-iframe" src="/storage/${email.mailBodyFilePath}"></iframe>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-center">
                            <div>
                                <button type="button"
                                    onclick="showMaterialDetails(this, '${email.name}', '${email.email_subject}', '${email.website}', '${email.senderProfile}')"
                                    class="btn btn-outline-primary btn-wave waves-effect waves-light mx-2">
                                    View
                                </button>
                            </div>
                            <div class="fs-semibold fs-14">
                                <input 
                                    type="checkbox" 
                                    name="phish_material" 
                                    class="btn-check"
                                    onclick="selectPhishingMaterial(this)" 
                                    data-phishMatName="${email.name}" 
                                    id="pm${email.id}" 
                                    value="${email.id}"
                                >
                                <label class="btn btn-outline-primary mb-3" for="pm${email.id}">Select this attack</label>

                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

    });

    return html;
}

$('#t_moduleSearch').on('input', function () {
    var searchValue = $(this).val().toLowerCase(); // Get the search value and convert it to lowercase

    clearTimeout($.data(this, 'timer'));
    if (searchValue.length > 2) {
        var wait = setTimeout(function () {
            // Call the search function here
            searchTrainingModule(searchValue);
        }, 2000);
        $(this).data('timer', wait);
    } else {
        if (searchedTrainingOnce) {
            fetchTrainingByCategory('all');
            searchedTrainingOnce = false;
        }
    }
});

let searchedTrainingOnce = false;

function searchTrainingModule(searchValue) {
    $('#trainingSearchSpinner').show();
    searchedTrainingOnce = true;
    // Loop through each template card
    $.post({
        url: '/campaigns/search-training-module',
        data: { search: searchValue },
        success: function (res) {
            if (res.status === 1) {
                // Clear existing results
                $('#trainingSearchSpinner').hide();
                $('#trainingModulesCampModal').empty()
                // Append new results
                const htmlrows = prepareTrainingHtml(res.data);
                $('#trainingModulesCampModal').append(htmlrows);
            } else {
                Swal.fire(res.msg, '', 'error');
            }
        }
    });
}



function prepareTrainingHtml(data) {
    let html = '';
    data.forEach(training => {
        html += `<div class="col-lg-6 t_modules">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center w-100">
                            <div class="">
                                <div class="fs-15 fw-semibold">${training.name}</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body htmlPhishingGrid">
                        <img class="trainingCoverImg" src="${training.cover_image ? '/storage/uploads/trainingModule/' + training.cover_image : '/storage/uploads/trainingGame/default.jpg'}" />
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-center">
                            <div class="fs-semibold fs-14">
                                <input type="checkbox" name="training_module" onclick="selectTrainingModule(this)" data-trainingName="${training.name}"
                                    value="${training.id}" class="btn-check" id="training${training.id}">
                                <label class="btn btn-outline-primary mb-3" for="training${training.id}">Select
                                    this
                                    training</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
    });

    return html;
}

function fetchTrainingByCategory(cat, type = 'static_training') {
    $.post({
        url: '/campaigns/fetch-training-by-category',
        data: {
            category: cat,
            type: type
        },
        success: function (res) {
            // console.log(res)
            if (res.status !== 1) {
                Swal.fire(
                    res.msg,
                    '',
                    'error'
                )
                return;
            }
            if (res.data.length === 0) {
                $('#trainingModulesCampModal').html('<div class="text-center py-3">No training modules found</div>');
                return;
            }
            const htmlrows = prepareTrainingHtml(res.data);
            $('#trainingModulesCampModal').html(htmlrows);
        }
    })
}

$("#training_cat").on('change', function () {

    const cat = $(this).val();
    const type = $('#training_type').val();
    if (type == 'gamified') {
        fetchTrainingByCategory(cat, type);
    } else {
        fetchTrainingByCategory(cat);
    }

})

$("#training_type").on('change', function () {

    const type = $(this).val();
    const category = $('#training_cat').val();
    if (type == 'gamified') {
        $("#training_cat_container").show();
        fetchTrainingByCategory(category, type);
    } else if (type == 'games') {
        $("#training_cat_container").hide();
        fetchTrainingByCategory(null, type);
    } else {
        $("#training_cat_container").show();
        fetchTrainingByCategory(category);
    }

})




$('#datatable-basic').DataTable({
    language: {
        searchPlaceholder: 'Search...',
        sSearch: '',
    },
    "pageLength": 10,
    // scrollX: true
});
