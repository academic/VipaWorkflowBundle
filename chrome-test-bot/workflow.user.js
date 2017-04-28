var domain = document.domain;
var url = document.URL;
var journalId = 2;

if(typeof(String.prototype.trim) === "undefined")
{
    String.prototype.trim = function()
    {
        return String(this).replace(/^\s+|\s+$/g, '');
    };
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function startArticleSubmission(){

    setCookie('articleSubmissionStarted', 1, 24);
    loginAsAuthor();
}

function loginAsAuthor(){
    var current_user = $('.user-menu .dropdown-menu li:eq(0) a').html();
    console.log('current user: '+ current_user);
    if(current_user == '@demo_author'){
        console.log('Correct author user.');
        window.location = 'http://vipa.dev/user';
    }else{
        window.location = "http://vipa.dev/logout";
    }
}

function clickArticleSubmitButton(){
    var subformCount = $('#parse-citations-button').parent().parent().parent().find('.submission-subform').length;
    if(subformCount < 1){
        console.log('not this time');
        return null;
    }
    console.log(typeof submittedArticleForm);
    if(typeof submittedArticleForm == 'boolean'){
        return;
    }
    submittedArticleForm = true;
    $('#vipa_article_submission_save').click();
    console.log('i clicked to submit form');
    setCookie('submitArticleOnPreview', 1, 8);
}

function fillArticleTrAbstract(){

    if(typeof abstractTransformed !== 'undefined'){
        return;
    }
    abstractTransformed = true;
    $('#vipa_article_submission_translations_tr_abstract').remove();
    $('form[name="vipa_article_submission"]').append('<input name="vipa_article_submission[translations][tr][abstract]" value="hello baby whats up" />');
}

if(url == "http://vipa.dev/#workflow-test"){

    startArticleSubmission();
}

if(url == "http://vipa.dev/"){
    if(getCookie('articleSubmissionStarted') == '1'){
        window.location = "http://vipa.dev/login";
    }
}

if(url == "http://vipa.dev/login"){
    if(getCookie('articleSubmissionStarted') == '1'){
        $("#username").val('demo_author');
        $("#password").val('demo');
        $("#password").parent().parent().find('button').click();
    }
}

if(url == 'http://vipa.dev/user'){
    if(getCookie('articleSubmissionStarted') == '1'){
        window.location = 'http://vipa.dev/journal/'+journalId+'/submission/start#workflow-test';
    }
}

if(url == 'http://vipa.dev/journal/'+journalId+'/submission/start#workflow-test'){
    $('#vipa_article_submission_checks_0').prop( "checked", true );
    $('#vipa_article_submission_checks_1').prop( "checked", true );
    $('#vipa_article_submission_checks_2').prop( "checked", true );
    $('#vipa_article_submission_save').click();
}

if(url == 'http://vipa.dev/journal/'+journalId+'/submission/new#workflow-test'){
    $('#vipa_article_submission_articleType').val(5);
    $('#vipa_article_submission_subjects').val([3,4]);
    $('#vipa_article_submission_translations_tr_title').val('hello workflow test tr title');
    $('#vipa_article_submission_translations_tr_keywords').append('<option selected>behram</option>\
    <option selected>workflow</option>\
    <option selected>test</option>');
    setInterval(function(){ fillArticleTrAbstract(); }, 1000);
    $('#raw-citations').val('Amann, Markus. Pulmonary system limitations to endurance exercise performance in humans. Exp Physiol, 2012; 97(3):311–18.');
    $('#parse-citations-button').click();
    $('#vipa_article_submission_articleFiles_0_file').val('article.txt');
    $('#vipa_article_submission_articleFiles_0_title').val('sample workflow test file title ');
    $('#vipa_article_submission_articleFiles_0_description').val('sample workflow test file description');

    setInterval(function(){ clickArticleSubmitButton(); }, 1000);
}

if(url.match(new RegExp('journal/'+journalId+'/submission/preview', 'g'))){
    if(getCookie('submitArticleOnPreview') == '1'){
        $('#vipa_article_submission_note').val('Merhaba editör bu ilk makalem lütfen kkabul edin!');
        var myJavaScript = "var confirm = false;";
        var scriptTag = document.createElement("script");
        scriptTag.innerHTML = myJavaScript;
        document.head.appendChild(scriptTag);

        $('#vipa_article_submission_submit').click();
    }
}

if(url.match(new RegExp('/journal/'+journalId+'/submission/me', 'g'))){
    window.location = 'http://vipa.dev/journal/'+journalId+'/workflow/active';
}

if(url.match(new RegExp('/journal/'+journalId+'/workflow/active', 'g'))){
    window.location = 'http://vipa.dev'+$('#flow-list tr:first td:eq(1) a').attr('href')+'#workflow-test';
}
