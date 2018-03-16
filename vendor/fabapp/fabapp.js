/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */

function endTicket(trans_id, device_desc) {
    var message = "Are you sure you want to end ";
    message = message.concat(device_desc);
    message = message.concat("\n\n\Ticket # ");
    message = message.concat(trans_id);
    var answer = confirm(message);
    if (answer){
        var dest = "/pages/end.php?trans_id=";
        dest = dest.concat(trans_id);
        window.location.href = dest;
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

function startTimer(duration, display, dg_parent) {
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

        if (timer == 0 && dg_parent == 1){
            window.alert("A Printer's time has expired");
            window.location.reload(1);
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