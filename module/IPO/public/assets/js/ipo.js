/*
 *  This file is part of Epeires².
 *  Epeires² is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  Epeires² is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Epeires².  If not, see <http://www.gnu.org/licenses/>.
 *
 */
var url;

var setUrl = function(urlt){
  url = urlt;  
};

$(document).ready(function(){

    
	$("#add-report").on('click', function(){
		$("#report-title").html("Nouveau rapport");
		$("#report-form").load(url+'/report/newreport');
	});
	
    
	$("#report-container").on('click', 'input[type=submit]', function(event){
		event.preventDefault();
		$.post(url+'/report/savereport', $("#Report").serialize(), function(data){
			if(data['messages']){
				displayMessages(data.messages);
			}
			if(data['success']){
				location.reload();
			}
		}, 'json').fail(function(){
			var messages = '({error: ["Impossible d\'enregistrer le rapport."]})';
			displayMessages(eval(messages));
		});
	});
});


