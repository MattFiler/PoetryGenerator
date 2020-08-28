// ***********************************************
// Artstation2020: Scripting for the backend site.
// ***********************************************

var characterData;
var authorData;
var characterSelectedIndex = 0;

/* Startup: load into characters */
function init() {
	refreshAllCharacters();
};

/* Refresh all character content */
function refreshAllCharacters() {
	$(".error_text").hide();
	$(".lds-dual-ring").show();
	$.ajax({
		url:"../php/get_characters.php",
		dataType: 'json',
		mimeType: 'multipart/form-data',
		contentType: false,
		cache: false,
		processData: false,
		success:function(data){
			characterData = data;
			
			var listHtml = "";
			for (var i = 0; i < data.length; i++) {
				listHtml += "<option value=\"" + data[i]["name"] + "\">" + data[i]["name"] + "</option>";
			}
			$(".character_list").html(listHtml);
			
			$.ajax({
				url:"../php/get_authors.php",
				dataType: 'json',
				mimeType: 'multipart/form-data',
				contentType: false,
				cache: false,
				processData: false,
				success:function(data){
					authorData = data;
					$(".character_list").prop('selectedIndex', characterSelectedIndex);
					loadCharacter();
				},
				error: function(jqXHR,status,error){
					$(".lds-dual-ring").hide();
					$(".error_text").show();
					$(".col-md-12").hide();
				}
			});
		},
		error: function(jqXHR,status,error){
			$(".lds-dual-ring").hide();
			$(".error_text").show();
			$(".col-md-12").hide();
		}
	});
};

/* Load a character to edit */
function loadCharacter() {
	characterSelectedIndex = $(".character_list").prop('selectedIndex');
	$(".lds-dual-ring").show();
	
	$(".character_name").val(characterData[characterSelectedIndex]["name"]);
	$(".character_era").val(characterData[characterSelectedIndex]["era"]);
	$(".character_image").val(characterData[characterSelectedIndex]["img_url"]);
	$(".character_location_lat").val(characterData[characterSelectedIndex]["location"]["lat"]);
	$(".character_location_lon").val(characterData[characterSelectedIndex]["location"]["lon"]);
	
	var listHtml = "";
	for (var i = 0; i < authorData.length; i++) {
		listHtml += "<input type=\"checkbox\" id=\"" + authorData[i]["name"] + "\" class=\"char_author_" + i + "\" value=\"" + authorData[i]["name"] + "\"";
		for (var x = 0; x < characterData[characterSelectedIndex]["authors"].length; x++) {
			if (characterData[characterSelectedIndex]["authors"][x] == authorData[i]["name"]) {
				listHtml += " checked";
				break;
			}
		}
		listHtml += "><label for=\"" + authorData[i]["name"] + "\">&nbsp;&nbsp;" + authorData[i]["name"] + " (<font color=\"";
		if (authorData[i]["size"] < 35000) listHtml += "red";
		if (authorData[i]["size"] < 100000) listHtml += "orange";
		else listHtml += "green";
		listHtml += "\">" + authorData[i]["size"] + " dataset</font>)</label><br>";
	}
	$(".author_list").html(listHtml);
	
	$(".lds-dual-ring").hide();
};

/* Save edited character */
function saveCharacter() {
	$(".lds-dual-ring").show();
	var selectedAuthors = [];
	for (var i = 0; i < authorData.length; i++) {
		if ($(".char_author_" + i).prop("checked")) {
			selectedAuthors.push(authorData[i]["name"]);
		}
	}
	var jform = new FormData();
	jform.append('index',characterSelectedIndex);
	jform.append('era',$(".character_era").val());
	jform.append('name',$(".character_name").val());
	jform.append('authors',selectedAuthors);
	jform.append('img_url',$(".character_image").val());
	jform.append('lat',$(".character_location_lat").val());
	jform.append('lon',$(".character_location_lon").val());
	$.ajax({
		url:"../php/save_character.php",
		data:jform,
		type:'POST',
		dataType: 'json',
		mimeType: 'multipart/form-data',
		contentType: false,
		cache: false,
		processData: false,
		success:function(data){
			$(".lds-dual-ring").hide();
			document.body.scrollTop = 0; 
			document.documentElement.scrollTop = 0;
			refreshAllCharacters();
		},
		error: function(jqXHR,status,error){
			$(".lds-dual-ring").hide();
			$(".error_text").show();
			$(".col-md-12").hide();
		}
	});
};

/* Refresh all soundscape info */
function refreshAllSoundscapes() {
	$(".lds-dual-ring").show();
	$(".error_text").hide();
	$.ajax({
		url:"../php/get_soundscapes.php",
		dataType: 'json',
		mimeType: 'multipart/form-data',
		contentType: false,
		cache: false,
		processData: false,
		success:function(data){
			var listHtml = "";
			for (var i = 0; i < data.length; i++) {
				listHtml += "<tr class='soundscape_display_" + data[i]["id"] + "'><td><ul>";
				for (var x = 0; x < data[i]["data"]["matching_words"].length; x++) {
					listHtml += "<li>" + data[i]["data"]["matching_words"][x] + "</li>";
				}
				listHtml += "</ul></td><td>" + data[i]["data"]["sound_url"] + "</td><td>";
				listHtml += "<button class='btn btn-danger' onClick='deleteSoundscape(" + data[i]["id"] + ");'>Delete</button></tr>";
			}
			$(".soundscape_table").html(listHtml);
			
			$(".lds-dual-ring").hide();
		},
		error: function(jqXHR,status,error){
			$(".lds-dual-ring").hide();
			$(".error_text").show();
			$(".col-md-12").hide();
		}
	});
};

/* Delete a soundscape entry */
function deleteSoundscape(id) {
	$(".lds-dual-ring").show();
	$.ajax({
		url:"../php/delete_soundscape.php?id=" + id,
		dataType: 'json',
		mimeType: 'multipart/form-data',
		contentType: false,
		cache: false,
		processData: false,
		success:function(data){
			$(".soundscape_display_" + id).hide();
			$(".lds-dual-ring").hide();
		},
		error: function(jqXHR,status,error){
			$(".lds-dual-ring").hide();
			$(".error_text").show();
			$(".col-md-12").hide();
		}
	});
}

/* Add a new soundscape */
function saveSoundscape() {
	$(".lds-dual-ring").show();
	var jform = new FormData();
	jform.append('keywords',$(".soundscape_keywords").val());
	jform.append('url',$(".soundscape_url").val());
	$.ajax({
		url:"../php/save_soundscape.php",
		data:jform,
		type:'POST',
		dataType: 'json',
		mimeType: 'multipart/form-data',
		contentType: false,
		cache: false,
		processData: false,
		success:function(data){
			$(".lds-dual-ring").hide();
			$(".soundscape_keywords").val("");
			$(".soundscape_url").val("");
			refreshAllSoundscapes();
		},
		error: function(jqXHR,status,error){
			$(".lds-dual-ring").hide();
			$(".error_text").show();
			$(".col-md-12").hide();
		}
	});
}