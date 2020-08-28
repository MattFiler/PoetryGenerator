// *****************************************************
// Artstation2020: Scripting for the main frontend site.
// *****************************************************

/*

One thing to note: this site used to allow users to select up to 3 words from the autogen list, and input one (or more) of their own.
This was removed when the themed poetry was introduced, but the systems for this are still implemented incase its needed again in future.
So if you're wondering why upload & analyse is split from poetry generation, that's why!

*/

/* On load */
function init() {
    $(".lds-dual-ring").hide();
    checkForRecall();
};

/* Submit the image upload form */
function sendImg() {
    if (document.getElementById("uploadFile").files.length == 0) return;
    
    $(".image_upload").prop("disabled", true);
    $(".submit_btn").prop("disabled", true);
    $(".error_text").text("");
    $(".lds-dual-ring").show();

    EXIF.getData($('.image_upload').get(0).files[0], function() {
        myData = this;
        var gpsLocation = [null, null];
        if (myData.exifdata.GPSLatitude) {
            var latFinal = convertDMSToDD(myData.exifdata.GPSLatitude, myData.exifdata.GPSLatitudeRef);
            var lonFinal = convertDMSToDD(myData.exifdata.GPSLongitude, myData.exifdata.GPSLongitudeRef);
            gpsLocation = [latFinal, lonFinal];
        }
        createPostcard(gpsLocation);
    });
};

/* Create a new base postcard, and allow the user to select some words */
var newPostcardID = -1;
function createPostcard(gpsLocation) {
    $(".step_1").fadeOut(500, function(){$(".step_2").fadeIn();});
    var jform = new FormData();
    jform.append('img',$('.image_upload').get(0).files[0]);
    jform.append('gps_lat',gpsLocation[0]);
    jform.append('gps_lon',gpsLocation[1]);
    $.ajax({
        url:"php/postcard_generator.php",
        data:jform,
        type:'POST',
        dataType: 'json',
        mimeType: 'multipart/form-data',
        contentType: false,
        cache: false,
        processData: false,
        success:function(data){
            if (data["error"] != "N/A") {
                postcardCreationFailed(data["error"]);
                return;
            }
            
            var buttonHTML = "";
            for (var i = 0; i < data["autogen_labels"].length; i++) {
                buttonHTML += "<p><button class='btn btn-primary word_button' onClick='pickWord(\"" + data["autogen_labels"][i]["word"] + "\", this, null);'>" + data["autogen_labels"][i]["word"] + "</button>";
                for (var x = 0; x < data["autogen_labels"][i]["synonyms"].length; x++) {
                    buttonHTML += "&nbsp;<button class='btn btn-secondary word_button' onClick='pickWord(\"" + data["autogen_labels"][i]["synonyms"][x] + "\", this, null);'>" + data["autogen_labels"][i]["synonyms"][x] + "</button>";
                }
                buttonHTML += "</p>";
            }
            var numOfCustoms = 1;
            if (data["autogen_labels"].length < 3) {
                numOfCustoms += 3 - data["autogen_labels"].length;
            }
            for (var i = 0; i < numOfCustoms; i++) {
                buttonHTML += "<div class=\"input-group mb-3\"><input type=\"text\" class=\"form-control custom_word_entry custom_word_entry_"+i+"\" placeholder=\"Custom word...\" maxlength=\"15\"><div class=\"input-group-append\"><button class=\"btn btn-primary custom_word_btn\" onClick='pickWord($(\".custom_word_entry_"+i+"\").val(), this, $(\".custom_word_entry_"+i+"\"));'>Select</button></div></div>";
            }
            $(".words_list").html(buttonHTML);

            newPostcardID = data["postcard_id"];

            $(".step_2").fadeOut(500, function(){$(".step_3").fadeIn();});
            $(".character_profilepic").attr("src", data["character"]["img_url"]);
            $(".custom_character_name").html(data["character"]["name"]);
            
            submitCustomWords(".step_3");
        },
        error: function(jqXHR,status,error){
            postcardCreationFailed(error);
        }
    });
};
function postcardCreationFailed(error) {
    $(".step_2").fadeOut(500, function() {
        $(".step_1").fadeIn(500, function() {
            $(".step_2").hide();
        });
    });
    $(".error_text").text(error);
    $(".image_upload").prop("disabled", false);
    $(".submit_btn").prop("disabled", false);
    $(".lds-dual-ring").hide();
};

/* Show the word list */
function showWordList() {
    $(".step_2").fadeOut(500, function(){$(".step_3").fadeIn();});
};

/* Allow user to select a new word, and then update & show the postcard */
var pickedWordList = [null,null,null];
function pickWord(thisWord, buttonRef, inputRef) {
    if (thisWord == "") return;

    $(buttonRef).prop("disabled", true);
    if (inputRef != null) $(inputRef).prop("disabled", true);

    if (pickedWordList[0] == null) pickedWordList[0] = thisWord;
    else if (pickedWordList[1] == null) pickedWordList[1] = thisWord;
    else if (pickedWordList[2] == null) {
        pickedWordList[2] = thisWord

        $(".word_button").prop("disabled", true);
        $(".custom_word_entry").prop("disabled", true);
        $(".custom_word_btn").prop("disabled", true);
        $(".error_text_words").text("");
        $(".lds-dual-ring").show();

        submitCustomWords(".step_3");
    }
};
function submitCustomWords(fromStep) {
    var jform = new FormData();
    jform.append('id',newPostcardID);
    jform.append('word_1',pickedWordList[0]);
    jform.append('word_2',pickedWordList[1]);
    jform.append('word_3',pickedWordList[2]);
    $.ajax({
        url:"php/postcard_updatewords.php",
        data:jform,
        type:'POST',
        dataType: 'json',
        mimeType: 'multipart/form-data',
        contentType: false,
        cache: false,
        processData: false,
        success:function(data){
            $(".fa-pause").show();
            $(".fa-play").hide();
            showPostcard(data, fromStep);
        },
        error: function(jqXHR,status,error){
            $(".error_text_words").text(error);
        }
    });
};

/* Re-roll the postcard */
function rerollPostcard() {
    if (newPostcardID == -1) return;
    $(".lds-dual-ring").show();
	$(".step_4").fadeOut(500, function(){$(".step_3").fadeIn();});
	submitCustomWords(".step_3");
    $(".thinking_text_override").html("Rethinking...");
};

/* Load an existing postcard */
function checkForRecall() {
    let searchParams = new URLSearchParams(window.location.search);
    if (!searchParams.has('id')) return;

    $(".image_upload").prop("disabled", true);
    $(".submit_btn").prop("disabled", true);
    $(".error_text").text("");
    $(".lds-dual-ring").show();

    let param = searchParams.get('id');
    $.ajax({
        url:"php/get_postcard.php?id=" + param,
        dataType: 'json',
        mimeType: 'multipart/form-data',
        contentType: false,
        cache: false,
        processData: false,
        success:function(data){
            $(".fa-pause").hide();
            $(".fa-play").show();
            showPostcard(data, ".step_1", 0);
        },
        error: function(jqXHR,status,error){
            $(".error_text").text(error);
            $(".image_upload").prop("disabled", false);
            $(".submit_btn").prop("disabled", false);
        }
    });
};

/* Show a postcard */
var thisPostcardID = "";
function showPostcard(data, fromStep, fadeTime=500) {
    if (data["error"] == "N/A") {
        if (typeof (history.pushState) != "undefined") {
            var obj = { Title: "similie", Url: "?id="+data["image_id"] };
            history.pushState(obj, obj.Title, obj.Url);
        }
        
        $(".error_text").text("");
        $(".error_text_words").text("");
        $(".lds-dual-ring").hide();
        thisPostcardID = data["image_id"];

        if (data["poem"] != null && data["poem"] != "") {
            var poemOutput = "";
            for (var i = 0; i < data["poem"].length; i++) {
                poemOutput += "<p>" + data["poem"][i].replace("???", "&apos;").replace("??", "&apos;") + "</p>";
            }
            $(".output_poem").html(poemOutput);
            $(".output_author").text(data["poem_author"]);
        }

        $(".output_image").attr("src", data["image_url"]);
        $(".output_sound").attr("src", data["soundscape_url"]);
        $(".output_url").val("https://" + $(location).attr('hostname') + "?id=" + thisPostcardID);
        $(".email_share_btn").attr("href", "mailto:?subject=Postcard&body=Check%20out%20my%20postcard!%0D" + $(".output_url").val());
        $(".twitter_share_btn").attr("href", "https://twitter.com/intent/tweet?text=Check%20out%20my%20postcard!%0D" + $(".output_url").val());
        document.querySelector(".output_sound").play();
        updatePlayPauseUI();
        
        if (newPostcardID != -1) {
            $(".postcard_reroll").show();
        }
        else {
            $(".postcard_reroll").hide();
        }

        $(fromStep).fadeOut(fadeTime, function(){$(".step_4").fadeIn(fadeTime, function(){updatePlayPauseUI();});});
    }
    else {
        $(".lds-dual-ring").hide();
        $(".error_text").text(data["error"]);
        $(".image_upload").prop("disabled", false);
        $(".submit_btn").prop("disabled", false);
    }
};

/* Copy the share URL to clipboard */
function copyShareURL() {
    $(".output_url").select();
    document.execCommand("copy");
};

/* Convert degrees, minutes, seconds & direction to decimal */
function convertDMSToDD(dms, direction) {
    var dd = dms[0] + (dms[1]/60) + (dms[2]/3600);
    if (direction == "S" || direction == "W") {
        dd = dd * -1; 
    }
    return dd;
};

/* Manually play/pause the soundscape */
function toggleSoundscape() {
    var audio = document.querySelector(".output_sound");
    if (audio.paused){
        audio.play();
    }
    else {
        audio.pause();
        audio.currentTime = 0;
    }
    updatePlayPauseUI();
};

/* Update the play/pause buttons accordingly */
function updatePlayPauseUI() {
    var audio = document.querySelector(".output_sound");
    if (audio.paused){
        $(".fa-pause").hide();
        $(".fa-play").show();
    }
    else {
        $(".fa-pause").show();
        $(".fa-play").hide();
    }
};