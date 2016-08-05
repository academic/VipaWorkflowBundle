jQuery(document).ready(function($) {
    var template = document.getElementById('journal_review_form_content'),
        $buildWrap = $(document.querySelector('.build-wrap')),
        renderWrap = document.querySelector('.render-wrap'),
        editBtn = document.getElementById('edit-form'),
        editing = true;

    var toggleEdit = function() {
        document.body.classList.toggle('editing-form', editing);
        $buildWrap.toggle();
        $(renderWrap).toggle();
        editing = !editing;
    };
    var options = {
        editOnAdd: true,
        disableFields: [
            'button',
            'autocomplete',
            'hidden',
            'file'
        ],
        roles: {
            1: 'Author can not see'
        }
    };
    $(template).formBuilder(options);

    $('.form-builder-save').click(function() {
        toggleEdit();
        $(template).formRender({
            container: renderWrap
        });
    });

    editBtn.onclick = function() {
        toggleEdit();
    };

    function removeUnnecessaryFields(){
        $('.form-elements .className-wrap').addClass('hidden');
        $('.form-elements .name-wrap').addClass('hidden');
    }
    //bind to div and on change remove unnecessary fields
    //this lines can be removal, because can trigger an browser crash
    $('.build-wrap').bind("DOMSubtreeModified",function(){
        removeUnnecessaryFields();
    });
    removeUnnecessaryFields();
});