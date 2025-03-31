//view users by group

function viewUsersByGroup(groupid) {
    $.get({
        url: "/employees/viewUsers/" + groupid,
        success: function (res) {
            if (res.status == 1) {
                // console.log(res);
                $(".addedUsers").empty();
                var userRows = "";
                $.each(res.data, function (index, value) {
                    userRows += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>
                        <a href="/employee/${btoa(
                            value.id
                        )}" class="text-primary" target="_blank">${
                        value.user_name
                    }</a>
                        </td>
                        <td>${value.user_email}</td>
                        <td>${value.user_company ?? "--"}</td>
                        <td>${value.user_job_title ?? "--"}</td>
                        <td>${value.whatsapp ?? "--"}</td>
                        <td><span class="text-danger ms-1" onclick="deleteUser('${
                            btoa(value.id)
                        }', '${
                        value.group_id
                    }');" role="button"><i class="bx bx-trash fs-4"></i></span></td>
                        </tr>`;
                });
                $(".addedUsers").html(userRows);
                $(".groupid").val(groupid);
                if (!$.fn.DataTable.isDataTable(".employeesTable")) {
                    $("#allUsersByGroupTable").DataTable({
                        language: {
                            searchPlaceholder: "Search...",
                            sSearch: "",
                        },
                        pageLength: 10,
                        // scrollX: true
                    });
                }
            } else {
                var emptyRow =
                    '<tr><td colspan="7" class="text-center">No employees available in this group!</td></tr>';
                $(".addedUsers").html(emptyRow);

                $(".groupid").val(groupid);
            }
        },
    });
}
function setGroupId(groupid) {
    document.getElementById("selectedGroupId").value = groupid;
}
function viewPlanUsers() {
    $.get({
        url: "/employees/viewPlanUsers/",
        success: function (res) {
            if (res.status == 1) {
                // console.log(res);
                $(".addedPlanUsers").empty();
                var userRows = "";
                $.each(res.data, function (index, value) {
                    userRows += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>
                        <a href="/employee/${btoa(
                            value.id
                        )}" class="text-primary" target="_blank">${
                        value.user_name
                    }</a>
                        </td>
                        <td>${value.user_email}</td>
                        <td>${value.user_company ?? "--"}</td>
                        <td>${value.user_job_title ?? "--"}</td>
                        <td>${value.whatsapp ?? "--"}</td>
                        <td><span class="text-danger ms-1" onclick="deleteUser('${
                            btoa(value.id)
                        }', '${
                        value.group_id
                    }');" role="button"><i class="bx bx-trash fs-4"></i></span></td>
                        </tr>`;
                });
                $(".addedPlanUsers").html(userRows);
                $(".groupid").val(groupid);
                if (!$.fn.DataTable.isDataTable(".employeesTable")) {
                    $("#allUsersByGroupTable").DataTable({
                        language: {
                            searchPlaceholder: "Search...",
                            sSearch: "",
                        },
                        pageLength: 10,
                        // scrollX: true
                    });
                }
            } else {
                var emptyRow =
                    '<tr><td colspan="6" class="text-center">No employees available in this group!</td></tr>';
                $(".addedPlanUsers").html(emptyRow);

                $(".groupid").val(groupid);
            }
        },
    });
}
// Function to send API request
let selectedUsers = []; // Store selected user IDs

// Function to send API request
function updateUsersGroup() {
    let groupid = document.getElementById("selectedGroupId").value;
    // Convert to integer
    console.log("Converted Group ID:", groupid);

    console.log("Adding users to group:", groupid);
    if (selectedUsers.length === 0) {
        alert("No users selected!");
        return;
    }

    $.ajax({
        url: "/employees/updateGroupUsers",
        type: "POST",
        data: {
            _token: $('meta[name="csrf-token"]').attr("content"),
            user_ids: selectedUsers,
            groupid: groupid,
        },
        success: function (res) {
            console.log(res);
            alert("Users successfully added to the group!");
        },
        error: function (xhr) {
            console.log(xhr.responseJSON); // Log validation errors
            alert("Error: " + xhr.responseText);
        },
    });
}

// Function to store selected user IDs
// function AddUser(userId, button) {
//     if (!selectedUsers.includes(userId)) {
//         selectedUsers.push(userId);

//         // Change button text to "Added" and disable further clicks
//         button.text("Added").addClass("text-muted").removeClass("text-primary");
//         button.closest("td").css("pointer-events", "none");
//     }
// }

// Function to fetch and display users in the table
function viewPlanAddUsers() {
    $.get({
        url: "/employees/viewPlanUsers/",
        success: function (res) {
            if (res.status == 1) {
                $(".addedPlanUsers").empty();
                var userRows = "";
                $.each(res.data, function (index, value) {
                    userRows += `
        <tr>
            <td>${index + 1}</td>
            <td>
                <a href="/employee/${btoa(
                    value.id
                )}" class="text-primary" target="_blank">
                    ${value.user_name}
                </a>
            </td>
            <td>${value.user_email}</td>
            <td>${value.user_company ?? "--"}</td>
            <td>${value.user_job_title ?? "--"}</td>
            <td>${value.whatsapp ?? "--"}</td>
            <td>
                <span class="text-primary ms-1 add-btn" data-id="${
                    value.id
                }" role="button">
                    Add
                </span>
            </td>
        </tr>`;
                });

                $(".addedPlanUsers").html(userRows);
                $(".groupid").val(groupid);

                // Initialize DataTable if not already initialized
                if (!$.fn.DataTable.isDataTable(".employeesTable")) {
                    $("#allUsersByGroupTable").DataTable({
                        language: {
                            searchPlaceholder: "Search...",
                            sSearch: "",
                        },
                        pageLength: 10,
                    });
                }

                // Add click event listener after table is updated
                $(".add-btn").click(function () {
                    var userId = $(this).data("id");
                    var button = $(this);

                    // Call the AddUser function
                    AddUser(userId, button);
                });
            } else {
                var emptyRow =
                    '<tr><td colspan="6" class="text-center">No employees available in this group!</td></tr>';
                $(".addedPlanUsers").html(emptyRow);

                $(".groupid").val(groupid);
            }
        },
    });
}

function AddUser(userId) {
    if (!selectedUsers.includes(userId)) {
        selectedUsers.push(userId);
    }
    console.log("selectedUsers", selectedUsers);
    // updateUsers();
}

function RemoveUser(userId) {
    selectedUsers = selectedUsers.filter((id) => id !== userId);
    console.log("selectedUsers", selectedUsers);

    // updateUsers();
}
function viewPlanAddUsers() {
    $.get({
        url: "/employees/viewPlanUsers/",
        success: function (res) {
            if (res.status == 1) {
                $(".addedPlanUsers").empty();
                var userRows = "";

                $.each(res.data, function (index, value) {
                    userRows += `
        <tr>
            <td>${index + 1}</td>
            <td>
                <a href="/employee/${btoa(
                    value.id
                )}" class="text-primary" target="_blank">
                    ${value.user_name}
                </a>
            </td>
            <td>${value.user_email}</td>
            <td>${value.user_company ?? "--"}</td>
            <td>${value.user_job_title ?? "--"}</td>
            <td>${value.whatsapp ?? "--"}</td>
            <td>
                <input type="checkbox" class="user-checkbox" data-id="${
                    value.id
                }">
            </td>
        </tr>`;
                });

                $(".addedPlanUsers").html(userRows);

                // if (!$.fn.DataTable.isDataTable(".employeesTable")) {
                //     $("#allUsersByGroupTable").DataTable({
                //         language: {
                //             searchPlaceholder: "Search...",
                //             sSearch: "",
                //         },
                //         pageLength: 10,
                //     });
                // }

                // Handle checkbox selection
                $(".user-checkbox").change(function () {
                    console.log("bunty");
                    var userId = $(this).data("id");

                    if ($(this).is(":checked")) {
                        AddUser(userId);
                    } else {
                        RemoveUser(userId);
                    }
                });
            } else {
                var emptyRow =
                    '<tr><td colspan="6" class="text-center">No employees available</td></tr>';
                $(".addedPlanUsers").html(emptyRow);

                // $(".groupid").val(groupid);
            }
        },
    });
}

function viewBlueUsersByGroup(groupid) {
    $.get({
        url: "/employees/viewBlueCollarUsers/" + groupid,
        success: function (res) {
            if (res.status == 1) {
                console.log("res", res);
                $(".addedBlueCollarUsers").empty();
                var userRows = "";
                $.each(res.data, function (index, value) {
                    userRows += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>
                        <a href="/employee/${btoa(
                            value.id
                        )}" class="text-primary" target="_blank">${
                        value.user_name
                    }</a>
                        </td>
                        
                        <td>${value.user_company ?? "--"}</td>
                        <td>${value.user_job_title ?? "--"}</td>
                        <td>${value.whatsapp ?? "--"}</td>
                        <td><span class="text-danger ms-1" onclick="deleteBlueUser('${
                            value.id
                        }', '${
                        value.group_id
                    }');" role="button"><i class="bx bx-trash fs-4"></i></span></td>
                        </tr>`;
                });
                $(".addedBlueCollarUsers").html(userRows);
                $(".groupid").val(groupid);
                if (!$.fn.DataTable.isDataTable(".employeesTable")) {
                    $("#allUsersByGroupTable").DataTable({
                        language: {
                            searchPlaceholder: "Search...",
                            sSearch: "",
                        },
                        pageLength: 10,
                        // scrollX: true
                    });
                }
            } else {
                var emptyRow =
                    '<tr><td colspan="6" class="text-center">No employees available in this group!</td></tr>';
                $(".addedBlueCollarUsers").html(emptyRow);

                $(".groupid").val(groupid);
            }
        },
    });
}

//validating whatsapp field
$("#usrWhatsapp").on("input", function () {
    var usrWhatsapp = $(this).val();

    // Validate the usrWhatsapp value
    if (/^\d{11,13}$/.test(usrWhatsapp)) {
        // The WhatsApp number is valid
        $(this).removeClass("is-invalid").addClass("is-valid");
    } else {
        // The WhatsApp number is invalid
        $(this).removeClass("is-valid").addClass("is-invalid");
    }
});

///adding user using form

$("#adduserForm").submit(function (e) {
    e.preventDefault();

    if (!$("#usrWhatsapp").hasClass("is-invalid")) {
        var formData = $(this).serialize();

        // console.log(formData.usrWhatsapp);
        $.post({
            url: "/employees/addUser",
            data: formData,
            success: function (res) {
                if (res.status == 0) {
                    // alert(resJson.msg);
                    Swal.fire(res.msg, "", "error");
                } else {
                    var params = new URLSearchParams(formData);
                    var groupid = params.get("groupid");
                    viewUsersByGroup(groupid);
                }
            },
        });
        // console.log(formData);
        $(this).trigger("reset");
        $("#usrWhatsapp").removeClass("is-valid");
    }
});
$("#adduserPlanForm").submit(function (e) {
    e.preventDefault();

    if (!$("#usrWhatsapp").hasClass("is-invalid")) {
        var formData = $(this).serialize();
        console.log("formData", formData);
        // return;
        // console.log(formData.usrWhatsapp);
        $.post({
            url: "/employees/addPlanUser",
            data: formData,
            success: function (res) {
                if (res.status == 0) {
                    // alert(resJson.msg);
                    Swal.fire(res.msg, "", "error");
                } else {
                    var params = new URLSearchParams(formData);
                    // var groupid = params.get("groupid");
                    viewPlanUsers();
                }
            },
        });
        // console.log(formData);
        $(this).trigger("reset");
        $("#usrWhatsapp").removeClass("is-valid");
    }
});
$("#addbluecollaruserForm").submit(function (e) {
    e.preventDefault();

    if (!$("#usrWhatsapp").hasClass("is-invalid")) {
        var formData = $(this).serialize();

        // console.log(formData.usrWhatsapp);
        $.post({
            url: "/employees/addBlueCollarUser",
            data: formData,
            success: function (res) {
                if (res.status == 0) {
                    // alert(resJson.msg);
                    Swal.fire(res.msg, "", "error");
                } else {
                    var params = new URLSearchParams(formData);
                    var groupid = params.get("groupid");
                    viewBlueUsersByGroup(groupid);
                }
            },
        });
        // console.log(formData);
        $(this).trigger("reset");
        $("#usrWhatsapp").removeClass("is-valid");
    }
});

//deleting employee group
function deleteGroup(grpId) {
    Swal.fire({
        title: "Are you sure?",
        text: "If this group is assigned with any live campaign then the campaign will be deleted. Are you sure ?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#e6533c",
        cancelButtonColor: "#d33",
        confirmButtonText: "Delete",
    }).then((result) => {
        if (result.isConfirmed) {
            $.post({
                url: "/employees/deleteGroup",
                data: {
                    // "deleteEmpGroup": "1",
                    group_id: grpId,
                },
                success: function (response) {
                    // console.log("deleted successfully")
                    // alert(response);
                    // window.location.reload()
                    if (response.status == 1) {
                        Swal.fire(response.msg, "", "success").then(() => {
                            window.location.href = window.location.href;
                        });
                    } else {
                        Swal.fire(response.msg, "", "error").then(() => {
                            window.location.href = window.location.href;
                        });
                    }
                },
            });
        }
    });
}

//deleting employee group
function deleteBlueCollarGroup(grpId) {
    Swal.fire({
        title: "Are you sure?",
        text: "If this group is assigned with any live campaign then the campaign will be deleted. Are you sure ?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#e6533c",
        cancelButtonColor: "#d33",
        confirmButtonText: "Delete",
    }).then((result) => {
        if (result.isConfirmed) {
            $.post({
                url: "/employees/deleteBlueGroup",
                data: {
                    // "deleteEmpGroup": "1",
                    group_id: grpId,
                },
                success: function (response) {
                    console.log("response", response);
                    // console.log("deleted successfully")
                    // alert(response);
                    // window.location.reload()
                    if (response.status == 1) {
                        Swal.fire(response.msg, "", "success").then(() => {
                            window.location.href = window.location.href;
                        });
                    } else {
                        Swal.fire(response.msg, "", "error").then(() => {
                            window.location.href = window.location.href;
                        });
                    }
                },
            });
        }
    });
}

function deleteUser(usrId, grpId) {
    Swal.fire({
        title: "Are you sure?",
        text: "This user will be deleted from Live campaign or scheduled campaign. And if this user has assigned any training then the learning account will be deleted.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#e6533c",
        cancelButtonColor: "#d33",
        confirmButtonText: "Delete",
    }).then((result) => {
        if (result.isConfirmed) {
            $.post({
                url: "/employees/deleteUser",
                data: {
                    user_id: usrId,
                },
                success: function (response) {
                    const groupId = document.getElementById("selectedGroupId").value;
                    viewUsersByGroup(groupId);
                },
            });
        }
    });
}
function deletePlanUser(usrId) {
    Swal.fire({
        title: "Are you sure?",
        text: "This user will be deleted from Live campaign or scheduled campaign. And if this user has assigned any training then the learning account will be deleted.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#e6533c",
        cancelButtonColor: "#d33",
        confirmButtonText: "Delete",
    }).then((result) => {
        if (result.isConfirmed) {
            $.post({
                url: "/employees/deleteUser",
                data: {
                    user_id: usrId,
                },
                success: function (response) {
                    Swal.fire({
                        title: "Deleted!",
                        text: "User has been deleted successfully.",
                        icon: "success",
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => {
                        location.reload(); // Refresh the page after deletion
                    });
                },
                error: function () {
                    Swal.fire("Error", "Something went wrong!", "error");
                },
            });
        }
    });
}

function deleteBlueUser(usrId, grpId) {
    Swal.fire({
        title: "Are you sure?",
        text: "This user will be deleted from Live campaign or scheduled campaign. And if this user has assigned any training then the learning account will be deleted.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#e6533c",
        cancelButtonColor: "#d33",
        confirmButtonText: "Delete",
    }).then((result) => {
        if (result.isConfirmed) {
            $.post({
                url: "/employees/deleteBlueUser",
                data: {
                    user_id: usrId,
                },
                success: function (response) {
                    // addedUsers(grpId)
                    viewBlueUsersByGroup(grpId);
                    // viewUsersByGroup(grpId);
                },
            });
        }
    });
}

$("#newDomainVerificationModalBtn").click(function () {
    $("#newDomainVerificationModal").modal("show"); // Show the second modal
});

//sending domain verification otp
$("#sendOtpForm").submit(function (e) {
    e.preventDefault();
    var formData = $(this).serialize();
    $("#otpSpinner").removeClass("d-none");
    $("#sendOtpBtn").addClass("d-none");

    $.post({
        url: "/employees/send-domain-verify-otp",
        data: formData,
        success: function (response) {
            $("#otpSpinner").addClass("d-none");
            $("#sendOtpBtn").removeClass("d-none");
            if (response.status == 0) {
                Swal.fire(response.msg, "", "error");

                $("#enterOtpContainer").addClass("d-none");
            } else {
                Swal.fire(response.msg, "", "success");
                $("#enterOtpContainer").removeClass("d-none");
            }
            // console.log(response);
        },
    });
    // console.log(formData);
});

//submitting otp and verify
$("#otpSubmitForm").submit(function (e) {
    e.preventDefault();
    var formData = $(this).serialize();
    $("#otpSubmitSpinner").removeClass("d-none");
    $("#otpSubmitBtn").addClass("d-none");

    $.post({
        url: "/employees/otp-verify",
        data: formData,
        success: function (response) {
            // alert(jsonres.msg);
            // window.location.reload()
            if (response.status == 1) {
                Swal.fire(
                    response.msg,
                    "Now you can add email of employees of this domain",
                    "success"
                );
                setTimeout(() => {
                    window.location.href = window.location.href;
                }, 2000);
            } else {
                Swal.fire(response.msg, "", "error");

                $("#otpSubmitSpinner").addClass("d-none");
                $("#otpSubmitBtn").removeClass("d-none");
            }

            // console.log(response);
        },
    });
    // console.log(formData);
});

function deleteDomain(id) {
    Swal.fire({
        title: "Are you sure?",
        text: "All employees will be deleted from Group whose email associated with this domain.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#e6533c",
        cancelButtonColor: "#d33",
        confirmButtonText: "Delete",
    }).then((result) => {
        if (result.isConfirmed) {
            $.post({
                url: "/employees/delete-domain",
                data: {
                    vDomainId: id,
                },
                success: function (response) {
                    if (response.status == 1) {
                        Swal.fire("Deleted!", response.msg, "success");
                    } else {
                        Swal.fire("Something went wrong!", "", "error");
                    }

                    setTimeout(() => {
                        window.location.href = window.location.href;
                    }, 2000);
                },
            });
        }
    });
}
