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
        $('.className-wrap').addClass('hidden');
        $('.name-wrap').addClass('hidden');
    }
    //bind to div and on change remove unnecessary fields
    $('.build-wrap').bind("DOMSubtreeModified",function(){
        removeUnnecessaryFields();
    });
});