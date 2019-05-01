/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */

    
function change_dot(){
    if (window.XMLHttpRequest) {
        // code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    } else {
        // code for IE6, IE5
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("dot_span").innerHTML = this.responseText;
        }
    };

    xmlhttp.open("GET","/pages/sub/getDot.php?d_id="+ document.getElementById("devices").value, true);
    xmlhttp.send();
}

function endTicket(trans_id, device_desc, lc) {
    if (lc == "N"){
        var dest = "/pages/end.php?trans_id=";
        dest = dest.concat(trans_id);
        window.location.href = dest;
    } else {
        var message = "End "+device_desc+"?";
        message = message.concat("\nPlease Enter the Ticket # ");
        var answer = prompt(message);
        if (answer == trans_id){
            var dest = "/pages/end.php?trans_id=";
            dest = dest.concat(trans_id);
            window.location.href = dest;
        } else {
            alert("Please Enter the Correct Ticket # for "+device_desc+".");
        }
    }
}

//fire off modal & optional dismissal timer
function goModal(title, body, auto){
    document.getElementById("modal-title").innerHTML = title;
    document.getElementById("modal-body").innerHTML = body;
    $('#popModal').modal('show');
    if (auto) {
        setTimeout(function(){$('#popModal').modal('hide')}, 6000);
    }
}

function loadingModal(){
    $('#loadingModal').modal('show');
}

function newTicket(){
    var device_id = document.getElementById("devGrp").value;
    var o_id = document.getElementById("operator_ticket").value;
    var o_id1 = document.getElementById('processOperator').innerHTML;

    if (device_id){
        if (o_id.substring(6,10) == o_id1.substring(7,11)){
            if("D_" === device_id.substring(0,2)){
                device_id = device_id.substring(2);
            } else{
                if("-" === device_id.substring(4,5)){
                device_id = device_id.substring(5);
                } else{
                device_id = device_id.substring(6);
                }
            }

            device = "d_id=" + device_id + "&operator=" + o_id;
            var dest = "";
            if (device  != "" && o_id.length==10){
                if (device_id.substring(0,1) == "2"){
                    dest = "http://polyprinter-"+device_id.substring(1)+".uta.edu";
                    window.open(dest,"_self")
                }
                else {
                    var dest = "/pages/create.php?";
                    dest = dest.concat(device);
                    console.log(dest);
                    window.location.href = dest;
                } 
            } 
            else {
                if (o_id.length!=10){
                    message = "Bad Operator Number: "+o_id;
                    var answer = alert(message);
                    }
                else{
                    message = "Please select a device.";
                    var answer = alert(message);
                }
            }
        }
        else {
            message = "Operator Number Does Not Match Next In Queue: "+o_id;
            var answer = alert(message);
        }
    } 
    else {
        message = "You must select a Device Group";
        var answer = alert(message);
    }
} 

function searchF(){
    var sForm = document.forms['searchForm'];
    if (sForm.searchType[0].checked == true) {
        sForm.searchField.type="number";
        sForm.searchField.placeholder="Search...";
        sForm.searchField.min = "1";
        sForm.searchField.autofocus=true;
    }
    if (sForm.searchType[1].checked == true) {
        sForm.searchField.type="text";
        sForm.searchField.placeholder="Enter ID #";
        sForm.searchField.maxLength = 10;
        sForm.searchField.size="10";
        sForm.searchField.autofocus=true;
    }
}

function sendManualMessage(q_id, message, loc){  
    if (confirm("You are about to send a notification to a wait queue user. Click OK to continue or CANCEL to quit.")){
        if (loc == 0){
            window.location.href = "/pages/sub/endWaitList.php?q_id=" + q_id + "&message=" + message + "&loc=0";
        }
        else if (loc == 1){
            window.location.href = "/pages/sub/endWaitList.php?q_id=" + q_id + "&message=" + message + "&loc=1";
        }
    }
}

function startTimer(duration, display) {
    var timer = duration, hours, minutes, seconds;
    setInterval(function () {
        if (timer > 0) {
            hours = parseInt(timer / 3600, 10);
            minutes = parseInt( (timer-(hours*3600))/60, 10);
            seconds = parseInt(timer % 60, 10);
            hours = hours < 10 ? hours : hours;
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = hours + ":" + minutes + ":" + seconds;
            --timer;
        } else {
            hours = Math.abs(parseInt(timer / 3600, 10));
            minutes = Math.abs(parseInt( (timer+(hours*3600))/60, 10));
            seconds = Math.abs(parseInt(timer % 60, 10));
            hours = hours < 10 ? hours : hours;
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = "- "+ hours + ":" + minutes + ":" + seconds;
            display.className="message";
            --timer;
        }   
    }, 1000);
}

//Standard regEx call for any input field
function stdRegEx(elementId, reg, msg){
    var x = document.getElementById(elementId).value;
    if (x === null || x === "" || !reg.test(x)) {
        alert(msg);
        document.getElementById(elementId).focus();
        return false;
    }
    return true;
}

//check forms for proper types
function validateNum(caller) {
    if ( caller.localeCompare("pickForm") == 0 ){
        var x = document.forms[caller]["pickField"].value;
        //return IDCheck(x);
        return stdRegEx("pickField", /^\d{10}$/, "Please enter ID #");
    } else if ( caller.localeCompare("searchForm") == 0 ){
        var x = document.forms[caller]["searchField"].value;
        var sForm = document.forms['searchForm'];
        if (sForm.searchType[0].checked) {
            var reg = /^\d{1,}$/;
            //Mav ID Check
            if (x == null || x == "" || !reg.test(x)) {
                if (!reg.test(x)) {
                    msg = "Invalid Ticket #";
                    //msg = msg.concat(x);
                    alert(msg);
                }
                return false;
            }
        }
        if (sForm.searchType[1].checked) {
            //return IDCheck(x);
            return stdRegEx("searchField", /^\d{10}$/, "Please enter ID #");
        }
    }
}

//Toggle Focus
$("#loginlink").on('click', function(){
    console.log("loginlink focus");
    var x = setTimeout('$("#netID").focus()', 200);
});
$("#pickLink").on('click', function(){
    console.log("pickLink focus");
    var x = setTimeout('$("#pickField").focus()', 200);
});
$("#searchLink").on('click', function(){
    console.log("searchLink focus");
    var x = setTimeout('$("#searchField").focus()', 200);
});