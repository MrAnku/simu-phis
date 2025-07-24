
//{csrf}//


const params = new URLSearchParams(window.location.href);
const campid = params.get('token');
const userid = params.get('usrid');
const tprmid = params.get('tprm') ?? false;
const qsh = params.get('qsh') ?? false;
const smi = params.get('smi') ?? false;
const wsh = params.get('wsh') ?? false;

let redirectUrl = '';
let alertPage = '';

if (campid && userid) {

  updatePayloadClick(campid, userid, tprmid, qsh, smi, wsh);
  
  checkWhereToRedirect(campid, tprmid, qsh, smi, wsh)
    .then((res) => {
      if (res.redirect !== 'simuEducation') {
        redirectUrl = res.redirect_url;
      } else {
        $.ajax({
          url: "/show/ap?lang=" + res.lang,
          dataType: "html",
          success: function (data) {
            // Replace entire HTML content with fetched content
            alertPage = data;
          }
        });
      }
    });

}

$("input").on('input', function(){
  if(!campid && !userid){
    return;
  }
  var inputLength = $(this).val().length;
  if (inputLength === 3){
    if(redirectUrl !== ''){
      updateCompromised(campid, userid, tprmid, qsh, smi, wsh);
      assignTraining(campid, userid, tprmid, qsh, smi, wsh);
      window.location.href = redirectUrl;
    } else{
      document.documentElement.innerHTML = alertPage;
      assignTraining(campid, userid, tprmid, qsh, smi, wsh);
      updateCompromised(campid, userid, tprmid, qsh, smi, wsh);
      
    }
  }
    
});

function checkWhereToRedirect(campid, tprm = false, qsh = false, smi = false, wsh = false) {
  // console.log(campid);
  return new Promise((resolve, reject) => {
    $.post({
      url: tprm !== false ? '/tcheck-where-to-redirect' : '/check-where-to-redirect',
      data: {
        checkWhereToRedirect: 1,
        qsh: qsh !== false ? 1 : 0,
        smi: smi !== false ? 1 : 0,
        wsh: wsh !== false ? 1 : 0,
        campid: campid
      },
      success: function (res) {
        resolve(res);
      },
      error: function (err) {
        reject(err);
      }
    });
  });
}

function assignTraining(campid, userid, tprm = false, qsh = false, smi = false, wsh = false) {
  if(tprm){
    return;
  }
  $.post({
    url: '/assignTraining',
    data: {
      assignTraining: 1,
      qsh: qsh !== false ? 1 : 0,
      smi: smi !== false ? 1 : 0,
      wsh: wsh !== false ? 1 : 0,
      campid: campid,
      userid: userid
    },
    success: function (res) {
      // console.log(res);
    }
  })
}

//email compromises =============================================

function updateCompromised(campid, userid, tprm = false, qsh = false, smi = false, wsh = false) {
  $.post({
    url: tprm !== false ? '/temp-compromised' : '/emp-compromised',
    data: {
      emailCompromised: 1,
      qsh: qsh !== false ? 1 : 0,
      smi: smi !== false ? 1 : 0,
      wsh: wsh !== false ? 1 : 0,
      campid: campid,
      userid: userid
    },
    success: function (res) {
      // console.log(res);
    }
  })
}

//payload handle===================================================

function updatePayloadClick(campid, userid, tprm = false, qsh = false, smi = false, wsh = false) {
  $.post({
    url: tprm !== false ? '/tupdate-payload' : '/update-payload',
    data: {
      updatePayloadClick: 1,
      qsh: qsh !== false ? 1 : 0,
      smi: smi !== false ? 1 : 0,
      wsh: wsh !== false ? 1 : 0,
      campid: campid,
      userid: userid
    },
    success: function (res) {
      // console.log(res);
    },
    error: function (err) {
      console.error('Error:', err);
    }
  });
}
