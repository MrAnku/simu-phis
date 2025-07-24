/* SCORM API CHAMILO PERFECT */

var QueueInteractions = new Array();
var QueueInteractions_count=0;
var LastScore = 0;

function QueueInteraction(){
	
	this.idnum;
	this.n;
	this.id;
	this.type;
	this.latency;
	this.result;
	this.answers;
	this.description;
	this.correctAnswer;
	this.isProcess;
	
	this.processQueue=function(){
		ScormInteractionChamilo(this.n,this.id,this.type,this.latency,this.result,this.answers,this.description,this.correctAnswer);
		this.isProcess = true;
	}
	
}

function QueueInteractions_Add(n,id,type,latency,result,answers,description,correctAnswer){
	
	Elem = new QueueInteraction();
	Elem.n = n;
	Elem.id = id;
	Elem.type = type;
	Elem.latency = latency;
	Elem.result = result;
	Elem.answers = answers;
	Elem.description = description;
	Elem.correctAnswer = correctAnswer;
	Elem.isProcess = false;
	Elem.idnum = QueueInteractions_count;
	QueueInteractions.push(Elem);
	QueueInteractions_count = QueueInteractions_count +1;
}

function QueueInteractionsProcessAll(){
	
	alertm("Sauvegarde en cours ..");
	$("#centermessage").css("top","0px").css("margin-top","20px");
	
	var t = 250;
	for(var i=0; i < QueueInteractions_count; i++){
		setTimeout("QueueInteractionsProcessOne(" + i + ");", t);
		t = t + 250;
	}
	t = t + 1500;
	setTimeout("QueueInteractionsisProcess();", t);
	
}

function QueueInteractionsisProcess(){
	
	for(var i=0; i < QueueInteractions_count; i++){
		if(QueueInteractions[i].isProcess==false){
			setTimeout("QueueInteractionsisProcess();", 1000);
			return false;
		}
	}
				
	API.Commit('');
	InteractionsSubmitted = true;
	CheckLMSFinishFinal();
	return true;
}	

function QueueInteractionsProcessOne(i){
	
	if(QueueInteractions[i].isProcess==false){
		$("#centermessageinner").html("<p>Sauvegarde en cours ..</p>");
		QueueInteractions[i].processQueue();
	}
		
}

var autoFinishScore = true;
var sendInteractionsScorm = true;

var API = null;
var callAPI = 0;

//Log Console
function logconsole(msg){

	if (typeof console === "undefined" || typeof console.log === "undefined"){
		
	}else{
		console.log(msg)
	}

}

/* Check SCORM API or AlterScorm */
function findAPI(win){

	callAPI = callAPI + 1;

	try{

		if (typeof(win.API_1484_11) != "undefined") {
			if(win.API_1484_11!=null){
				API = win.API_1484_11;
				logconsole("FIND win.API_1484_11");
				return true;
			}
		}
		
		while ((win.API_1484_11 == null) && (win.parent != null) && (win.parent != win) && callAPI<10)
		{
			
			var alterwin = win.parent;
			
			if (typeof(alterwin.API_1484_11) != "undefined") {
				if(alterwin.API_1484_11!=null){
					API = alterwin.API_1484_11;
					logconsole("FIND win.API_1484_11");
					return true;
				}
			}
			
			callAPI = callAPI + 1;

		}
		
		callAPI = 0;
		
		while ((win.API == null) && (win.parent != null) && (win.parent != win) && callAPI<10)
		{
			win = win.parent;
			logconsole("win = win.parent");

			callAPI = callAPI + 1;

		}
		
		API = win.API;
		
	}catch(exception){
		
		logconsole("findAPI error");
		return false;
		
	}

}

/* initialize the SCORM API */
function initAPI(win){
	
	logconsole("initAPI start");
	
	try{

		/* look for the SCORM API up in the frameset */
		findAPI(win);
		
		/* if we still have not found the API, look at the opener and its frameset */
		if ((API == null) && (win.opener != null))
		{
			findAPI(win.opener);
		}

		logconsole("initAPI end");

	}catch(exception){

		logconsole("findAPI error");
		return false;

	}

}

var ScormSubmitted = false; //use this to check whether LMSFinish has been called later.
var InteractionsSubmitted = false; //use this to check whether LMSFinish has been called later.

function ScormStartCom(){

	ScormStartComProcess();
	
}

function ScormStartComProcess(){
	
	initAPI(window);
	
	if (API != null){
		
		var initOk = false;
		
		//SCORM 2004
		if (typeof(API.Initialize) != "undefined"){
			var r = API.Initialize('');
			if(r==true||r=='true'){
				API.SetValue('cmi.core.lesson_status', 'incomplete');
				API.SetValue('cmi.core.score.min', 0);
				API.SetValue('cmi.core.score.max', 100);
				API.Commit('');
				logconsole("Initialize ScormStartCom");
				initOk = true;
			}else{
				logconsole("Initialize Error");
			}
		}

	}
	
}

function ScormInteractionCom(n,id,type,latency,result,answers,description,correctAnswer){
	correctAnswer =  "P" + LUDI.getNumPage() + '|' + correctAnswer;
	answers =  "P" + LUDI.getNumPage() + '|' + answers;
	QueueInteractions_Add(n,id,type,latency,result,answers,description,correctAnswer);
}

function ScormInteractionChamilo(n,id,type,latency,result,answers,description,correctAnswer){
	
	if(sendInteractionsScorm){
		
		//n : Ludiscape transmet le numÃ©ro de l'interaction
		//id : Ludiscape transmet une serie de donnes pour cette chaÃ®ne afin d'identifier la question
		//type : Type de question : qcm tcm drop etc
		//latency : Temps de reponse
		//result : Indique si l'utilisateur a rÃ©pondu correctement Ã  la question ou non.
		//answers : reponse de l'apprenant
		if (API != null){
			
			if (API){
				
				if(type=='qcm'||type=='qcmunique'||type=='choice'){
					type='choice';
				}else{
					type='performance';
				}
				
				type='choice';
				
				var FormatAnswersScorm = answers;
				FormatAnswersScorm = FormatAnswersScorm.replace("<strike>","");
				FormatAnswersScorm = FormatAnswersScorm.replace("</strike>","");
				if (FormatAnswersScorm.length > 250){
					FormatAnswersScorm = FormatAnswersScorm.substr(0, 250);
				}
				
				var FormatcorrectAnswer = correctAnswer;
				FormatcorrectAnswer = escapeSco(FormatcorrectAnswer);
				if (FormatcorrectAnswer.length > 150){
					FormatcorrectAnswer = FormatcorrectAnswer.substr(0, 150);
				}
				
				if (typeof(API.LMSSetValue) != "undefined") {

				
					API.LMSSetValue('interactions',"true");
				
					API.LMSSetValue('cmi.objectives.' + n + '.id', id);
					API.LMSSetValue('cmi.interactions.' + n + ".objectives.0.id", id ); 					
					API.LMSSetValue('cmi.objectives.' + n + '.status', API.LMSGetValue('cmi.core.lesson_status'));
					API.LMSSetValue('cmi.objectives.' + n + '.score.min', '0');
					API.LMSSetValue('cmi.objectives.' + n + '.score.max', '100');
					
					if(result){
						API.LMSSetValue('cmi.objectives.' + n + '.score.raw', '100');
					}else{
						API.LMSSetValue('cmi.objectives.' + n + '.score.raw', '0');
					}
						
						
					API.LMSSetValue('cmi.interactions.' + n + '.id', id)
					API.LMSSetValue('cmi.interactions.' + n + '.type', type);
					API.LMSSetValue('cmi.interactions.' + n + '.latency', latency);

					if(result){
						API.SetValue('cmi.interactions.' + n + '.result', 'correct');
					}else{
						API.SetValue('cmi.interactions.' + n + '.result', 'incorrect');
					}
					
					API.LMSSetValue('cmi.interactions.' + n + '.student_response', FormatAnswersScorm);
					API.LMSSetValue('cmi.interactions.' + n + '.student_response_text', FormatAnswersScorm);
					API.LMSSetValue('cmi.interactions.' + n + '.description', description);
					API.LMSSetValue('cmi.interactions.' + n + '.weighting', '1');
					
					if (FormatcorrectAnswer != undefined && FormatcorrectAnswer != null && FormatcorrectAnswer != ""){
						API.LMSSetValue("cmi.interactions." + n + ".correct_responses", FormatcorrectAnswer);
					}else{
						API.LMSSetValue("cmi.interactions." + n + ".correct_responses", '?');
					}
					
					//olms.interactions[n][4] = FormatAnswersScorm;
					
					API.LMSSetValue('cmi.session_time', MillisecondsToTime((new Date()).getTime() - ScormStartTime));

					
				}
				
				
			}
		}
	
	}
	
}

function sendLMSFinish(){
	
	if('function'==typeof(CheckLMSFinish)){
		ScormSubmitted = false;
		globalCompteurDecompt = false;
		CheckLMSFinish();
		$("#main").animate({marginTop : "-750px",height:"100px",opacity: 0},1500);
	}
	
}

function CheckLMSFinish(){
	
	if (API != null){
		if (ScormSubmitted == false){
			
			setTimeout(function(){
				alertm("Save interactions ...");
				QueueInteractionsProcessAll();
			},
			1000);
			
		}
	}
}

function CheckLMSFinishFinal(){
	
	if (ScormSubmitted == false&&InteractionsSubmitted){
		
		var cpl = 'completed';
		if(LastScore<100){
			cpl = 'incomplete';
		}
		
		API.LMSSetValue('cmi.core.session_time', MillisecondsToTime((new Date()).getTime() - ScormStartTime));
		var timefull = MillisecondsToTime2004((new Date()).getTime() - ScormStartTime);
		API.SetValue('cmi.core.session_time', timefull);
		API.SetValue('cmi.session_time', timefull);
		API.LMSSetValue('cmi.core.lesson_status',cpl);
		API.SetValue('cmi.core.lesson_status',cpl);
		API.SetValue('cmi.lesson_status',cpl);
		API.SetValue('cmi.completion_status',cpl);
		API.LMSCommit('');
		API.Commit('');
		
		API.LMSFinish('');
		
		ScormSubmitted = true;
		globalCompteurDecompt = false;
			
	}	

}

var MemUserN = '';

function CheckLMSLearnerName(){
	
	var userN = '';
	
	if(MemUserN!=''){
		return MemUserN;
	}
	
	//SCORM 2004
	if (API != null){
		if (typeof(API.data)!="undefined"){
			if (typeof(API.data.learner_name)!="undefined"){
				userN = API.data.learner_name;
			}
		}
		if(userN==''){
			if (typeof(API.LMSGetValue)!="undefined"){
				userN = API.LMSGetValue("cmi.core.student_name") ;
			}
		}
		if(userN==''){
			if (typeof(API.LMSGetValue)!="undefined"){
				userN = API.LMSGetValue("cmi.student_name");
			}
		}
		if(userN==''){
			if (typeof(API.LMSGetValue)!="undefined"){
				userN = API.LMSGetValue("cmi.core.student_id");
			}
		}
	}
	
	if(userN==''){
		if(typeof(API.GetValue)!="undefined"){
			userN = API.GetValue("cmi.learner_name");	
			if(userN==''){
				userN = API.GetValue("cmi.learner_id");
			}
		}
	}
	
	MemUserN = userN;
	MemUserN = MemUserN.replace(',',' ');
	MemUserN = MemUserN.replace('  ',' ');
	
	return MemUserN;

}

function SetScormIncomplete(){
	if (ScormSubmitted == true){
		return;
	}
	SetScormScore();
	if (API != null){
		//SCORM 1.2
		if (typeof(API.LMSSetValue) != "undefined") {
			API.LMSSetValue('cmi.core.lesson_status', 'incomplete');
			API.LMSSetValue('cmi.core.session_time', MillisecondsToTime((new Date()).getTime() - ScormStartTime));
			API.LMSCommit('');
		}
		//SCORM 2004
		if (typeof(API.Terminate) != "undefined") {
			API.SetValue('cmi.core.lesson_status', 'incomplete');
			API.SetValue('cmi.core.session_time', MillisecondsToTime((new Date()).getTime() - ScormStartTime));
			API.SetValue('cmi.lesson_status', 'incomplete');
			API.SetValue('cmi.session_time', MillisecondsToTime((new Date()).getTime() - ScormStartTime));
			API.Commit('');
		}
	}
}

var isScormFinish = false;

function SetScormComplete(){
	
	logconsole("SetScormComplete");
	
	if(isScormFinish==false){
		
		if (API != null){
			
			//SCORM 1.2
			if (typeof(API.LMSSetValue) != "undefined") {

				SetScormScore();
				if(autoFinishScore){
					CheckLMSFinish();
					API.LMSFinish('');
					isScormFinish = true;
				}
				
			}else{
				
				//SCORM 2004
				if (typeof(API.Terminate) != "undefined") {
					SetScormScore();
					if(autoFinishScore){
						CheckLMSFinish();
						isScormFinish = true;
					}

				}
				
				
			}
			


		}

	}

}

var ScormStartTime = (new Date()).getTime();
var SuspendData = '';

function SetScormTimedOut(){
	if (API != null){
		if (ScormSubmitted == false){
			
			//SCORM 1.2
			if (typeof(API.LMSSetValue) != "undefined") {
				SetScormScore();
				API.LMSSetValue('cmi.core.exit', 'time-out'); 
				API.LMSCommit('');
				CheckLMSFinish();
			}
			//SCORM 2004
			if (typeof(API.Terminate) != "undefined") {
				SetScormScore();
				API.SetValue('cmi.core.exit', 'time-out');
				API.SetValue('cmi.exit', 'time-out'); 
				API.Commit('');
				API.Terminate('');
			}

		}
	}
}

function SetScormComments(m){
	if (API != null){
		if (ScormSubmitted == false){

			//SCORM 1.2
			if (typeof(API.LMSSetValue) != "undefined") {
				API.LMSSetValue('cmi.comments', m); 
			}
			//SCORM 2004
			if (typeof(API.Terminate) != "undefined") {
				API.SetValue('cmi.comments', m); 
			}
			
		}
	}
} 

//TIME RENDERING FUNCTION
function MillisecondsToTime(Seconds){
	Seconds = Math.round(Seconds/1000);
	var S = Seconds % 60;
	Seconds -= S;
	if (S < 10){S = '0' + S;}
	var M = (Seconds / 60) % 60;
	if (M < 10){M = '0' + M;}
	var H = Math.floor(Seconds / 3600);
	if (H < 10){H = '0' + H;}
	return H + ':' + M + ':' + S;
}

//TIME RENDERING FUNCTION
function MillisecondsToTime2004(Seconds){
	Seconds = Math.round(Seconds/1000);
	var S = Seconds % 60;
	Seconds -= S;
	if (S < 10){S = '0' + S;}
	var M = (Seconds / 60) % 60;
	if (M < 10){M = '0' + M;}
	var H = Math.floor(Seconds / 3600);
	if (H < 10){H = '0' + H;}
	return 'PT' + H + 'H' + M + 'M' + S + 'S';
}

//ISO Date String
function ISODateString(d) {
    function pad(n) {return n<10 ? '0'+n : n}
    return d.getUTCFullYear()+'-'
         + pad(d.getUTCMonth()+1)+'-'
         + pad(d.getUTCDate())+'T'
         + pad(d.getUTCHours())+':'
         + pad(d.getUTCMinutes())+':'
         + pad(d.getUTCSeconds())+'Z'
}

//SetScormScore
function SetScormScore(score){
	
	if(typeof(score) != "undefined"){
		
		if (score != null){
			if (API != null){
				
				LastScore = score;
				//SCORM 1.2
				if (typeof(API.LMSSetValue) != "undefined") {
					API.LMSSetValue('cmi.core.score.raw', score);
				}
				//SCORM 2004
				if (typeof(API.Terminate) != "undefined") {
					API.SetValue('cmi.core.score.raw', score);
					API.SetValue('cmi.score.raw', score);
				}
			}
			logconsole("SetScormScore " + score);
		}
		
	}
	
}

function escapeSco(unsafe){
	
	unsafe = unsafe.toLowerCase();
	unsafe = unsafe.replace(/,/g, "virgulebase")
	unsafe = unsafe.replace(/[^a-zA-Z0-9]/g,'-');
	unsafe = unsafe.replace(/virgulebase/g, ",")
	return unsafe;
	
}

