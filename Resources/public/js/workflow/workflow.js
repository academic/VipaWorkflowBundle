$(document).ready(function() {
    $('.fancybox').fancybox({});
    VipaWorkflow = {
        basicJournalWfSetting: function($this) {
            $this = $($this);
            $.fancybox({
                type: 'ajax',
                href: $this.attr('href')
            });
        },
        updateJournalWfSetting: function () {
            var journalWfSettingForm = $('form[name="journal_wf_setting"]');
            $.post( journalWfSettingForm.attr('action'), journalWfSettingForm.serialize(), function( data ) {
                $.fancybox(data);
            });
        },
        stepGrantedUsersSetup: function($this, $stepOrder) {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('vipa_workflow_step_users_setup', {journalId: journalId, stepOrder: $stepOrder}),
                autoSize: false,
                width: '600px',
                maxWidth: '600px',
                height: 'auto'
            });
        },
        updateGrantedUsers: function() {
            var journalWfStepForm = $('form[name="journal_wf_step"]');
            $.post( journalWfStepForm.attr('action'), journalWfStepForm.serialize(), function( data ) {
                $.fancybox({
                    content: data,
                    type: 'inline',
                    autoSize: false,
                    width: '600px',
                    maxWidth: '600px',
                    height: 'auto'
                });
            });
        },
        setupWorkflowGrantedUsers: function($this) {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('vipa_article_workflow_granted_users_setup', {
                    journalId: journalId,
                    workflowId: workflowId
                }),
                autoSize: false,
                width: '600px',
                maxWidth: '600px',
                height: 'auto'
            });
        },
        updateWorkflowGrantedUsers: function() {
            var articleWfGrantedUsersForm = $('form[name="article_wf_granted_users"]');
            $.post( articleWfGrantedUsersForm.attr('action'), articleWfGrantedUsersForm.serialize(), function( data ) {
                $.fancybox({
                    content: data,
                    type: 'inline',
                    autoSize: false,
                    width: '600px',
                    maxWidth: '600px',
                    height: 'auto'
                });
            });
        },
        showStepDetail: function($this, $stepOrder) {
            $.fancybox({
                type: 'inline',
                href: '#wf-step-description-'+$stepOrder
            });
        },
        showSubmissionDetail: function () {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('vipa_workflow_article_detail', {
                    journalId: journalId,
                    workflowId: workflowId
                })
            });
        },
        loadStep: function ($stepOrder) {
            $.get(Routing.generate('vipa_workflow_timeline_step', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: $stepOrder
            }), function( data ) {
                var stepOrder = $stepOrder;
                $.each($('.btn-breadcrumb a'), function(index,value){
                    $(value).removeClass('btn-primary');
                });
                $('#workflow-step-'+$stepOrder).addClass('btn-primary');
                $('.timeline').html(data);
            });
        },
        loadDialogs: function () {
            $.get(Routing.generate('dp_workflow_step_dialogs', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder
            }), function( data ) {
                $('#dialogs-box-'+stepOrder).html(data);
            });
        },
        loadPosts: function ($dialogId) {
            $.get(Routing.generate('dp_workflow_dialog_posts', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId
            }), function( data ) {
                $('#dialog-posts-'+$dialogId).html(data);
            });
        },
        finishDialogAction: function ($dialogId) {
            $.get(Routing.generate('dp_workflow_dialog_finish', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId
            }), function( data ) {
                if(data.success == true){
                    VipaWorkflow.loadDialogs();
                    swal(
                        Translator.trans('successful'),
                        Translator.trans('successful.finished.action'), "success"
                    );
                }
            });
        },
        reopenDialog: function ($dialogId) {
            $.get(Routing.generate('dp_workflow_dialog_reopen', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId
            }), function( data ) {
                if(data.success == true){
                    VipaWorkflow.loadDialogs();
                    swal(
                        Translator.trans('successful'),
                        Translator.trans('successful.reopen.action'), "success"
                    );
                }
            });
        },
        removeDialog: function ($dialogId) {
            $.get(Routing.generate('dp_workflow_dialog_remove', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId
            }), function( data ) {
                if(data.success == true){
                    VipaWorkflow.loadDialogs();
                    swal(
                        Translator.trans('successful'),
                        Translator.trans('successful.remove.action'), "success"
                    );
                }
            });
        },
        showHistoryLog: function ($this) {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('vipa_workflow_history_log', {
                    journalId: journalId,
                    workflowId: workflowId
                })
            });
        },
        showPermissionTable: function ($this) {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('vipa_workflow_permission_table', {
                    journalId: journalId,
                    workflowId: workflowId
                })
            });
        },
        getActionType: function($this){
            return $($this).attr('data-action-type');
        },
        refreshAgoPlugin: function(){
            $('abbr.ago').each(function () {
                $(this).livestamp($(this).attr('title'));
            });
        },
        refreshTooltip: function () {
            $("[data-toggle=tooltip]").tooltip();
        },
        refreshRichTextEditorPlugin: function () {
            $('.wysihtml5').each(function () {
                var wysihtml5 = $(this);
                wysihtml5.summernote({
                    height: 100,                 // set editor height

                    minHeight: null,             // set minimum height of editor
                    maxHeight: null,             // set maximum height of editor

                    focus: false,                 // set focus to editable area after initializing summernote
                    toolbar: [
                        ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['table', ['table']],
                        ['insert', ['link', 'picture', 'hr']],
                        ['view', ['codeview']]
                    ]
                });

                $('form').on('submit', function () {
                    if (wysihtml5.summernote('isEmpty')) {
                        wysihtml5.val('');
                    } else if (wysihtml5.val() == '<p><br></p>') {
                        wysihtml5.val('');
                    }
                });

            });
        },
        refreshTagsInputPlugin: function () {
            var tagAutocompleteInput = $('select[data-role=tagsinputautocomplete]');
            tagAutocompleteInput.select2({
                ajax: {
                    data: function (params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                templateResult: function (user) {
                    return user.text;
                },
                templateSelection: function (user) {
                    return user.text;
                }
            });
            $('.select2-container').css('width', '100%');
        },
        createSpecificDialog: function ($this) {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_create_specific_dialog', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder,
                    actionType: this.getActionType($this)
                }),
                autoSize: false,
                width: '600px',
                maxWidth: '600px',
                height: 'auto'
            });
        },
        postSpecificDialog: function ($this) {
            var dialogForm = $('form[name="dialog"]');
            $.post( dialogForm.attr('action'), dialogForm.serialize(), function( data ) {
                $.fancybox({
                    content: data,
                    type: 'inline',
                    autoSize: false,
                    width: '600px',
                    maxWidth: '600px',
                    height: 'auto'
                });
            });
        },
        //we not using this function for now but i will remove this function
        // two week later
        createDialogWithAuthor: function($this){
            $.get( Routing.generate('dp_workflow_create_dialog_with_author', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                actionType: this.getActionType($this)
            }), function( data ) {
                $.fancybox({
                    content: data,
                    type: 'inline',
                    autoSize: false,
                    width: '600px',
                    maxWidth: '600px',
                    height: 'auto'
                });
            });
        },
        createBasicDialog: function($this){
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_create_basic_dialog', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder,
                    actionType: this.getActionType($this)
                }),
                autoSize: false,
                width: '600px',
                maxWidth: '600px',
                height: 'auto'
            });
        },
        postBasicDialog: function ($this) {
            var dialogForm = $('form[name="dialog"]');
            $.post( dialogForm.attr('action'), dialogForm.serialize(), function( data ) {
                $.fancybox({
                    content: data,
                    type: 'inline',
                    autoSize: false,
                    width: '600px',
                    maxWidth: '600px',
                    height: 'auto'
                });
            });
        },
        createAssignReviewerDialog: function($this){
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_create_assign_reviewer_dialog', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder,
                    actionType: this.getActionType($this)
                }),
                autoSize: false,
                width: '600px',
                maxWidth: '600px',
                height: 'auto'
            });
        },
        postAssignReviewerDialog: function ($this) {
            var dialogForm = $('form[name="dialog"]');
            $.post( dialogForm.attr('action'), dialogForm.serialize(), function( data ) {
                $.fancybox({
                    content: data,
                    type: 'inline',
                    autoSize: false,
                    width: '600px',
                    maxWidth: '600px',
                    height: 'auto'
                });
            });
        },
        createAssignAuthorDialog: function($this){
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_create_dialog_with_author', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder,
                    actionType: this.getActionType($this)
                }),
                autoSize: false,
                width: '600px',
                maxWidth: '600px',
                height: 'auto'
            });
        },
        postAssignAuthorDialog: function ($this) {
            var dialogForm = $('form[name="dialog"]');
            $.post( dialogForm.attr('action'), dialogForm.serialize(), function( data ) {
                $.fancybox({
                    content: data,
                    type: 'inline',
                    autoSize: false,
                    width: '600px',
                    maxWidth: '600px',
                    height: 'auto'
                });
            });
        },
        acceptAndGotoArrangement: function($this){
            swal({
                title: Translator.trans('workflow.are.you.sure.goto.arrangement'),
                text: Translator.trans('workflow.goto.arrangement.warnings'),
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: Translator.trans('workflow.yes.goto.arrangement'),
                closeOnConfirm: false
            }, function() {
                $.get( Routing.generate('dp_workflow_accept_goto_arrangement', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder
                }), function( data ) {
                    if(data.success == true){
                        swal(
                            Translator.trans('successful'),
                            Translator.trans('successful.go.on.arrangement'), "success"
                        );
                        VipaWorkflow.loadStep(3);
                    }else{
                        alert('Some error occured');
                    }
                });
            });
        },
        gotoReviewing: function($this){
            swal({
                title: Translator.trans('workflow.are.you.sure.goto.reviewing'),
                text: Translator.trans('workflow.goto.reviewing.warnings'),
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: Translator.trans('workflow.yes.goto.reviewing'),
                closeOnConfirm: false
            }, function() {
                $.get( Routing.generate('dp_workflow_goto_reviewing', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder
                }), function( data ) {
                    if(data.success == true){
                        swal(
                            Translator.trans('successful'),
                            Translator.trans('successful.go.on.reviewing'), "success"
                        );
                        VipaWorkflow.loadStep(2);
                    }else{
                        alert('Some error occured');
                    }
                });
            });
        },
        acceptSubmission: function($this){
            swal({
                title: Translator.trans('workflow.are.you.sure.accept.submission'),
                text: Translator.trans('workflow.accept.submission.warnings'),
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: '#5fba7d',
                confirmButtonText: Translator.trans('workflow.yes.accept.submission'),
                closeOnConfirm: false
            }, function() {
                $.get( Routing.generate('dp_workflow_accept_submission', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder
                }), function( data ) {
                    if(data.success == true){
                        swal(
                            Translator.trans('successful'),
                            Translator.trans('successful.go.to.accepted.article'), "success"
                        );
                        window.location = data.data.redirectUrl;
                    }else{
                        alert('Some error occured');
                    }
                });
            });
        },
        declineSubmission: function($this){
            swal({
                title: Translator.trans('workflow.are.you.sure.decline.submission'),
                text: Translator.trans('workflow.decline.submission.warnings'),
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: Translator.trans('workflow.yes.decline.submission'),
                closeOnConfirm: false
            }, function() {
                $.get( Routing.generate('dp_workflow_decline_submission', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder
                }), function( data ) {
                    if(data.success == true){
                        swal(
                            Translator.trans('successful'),
                            Translator.trans('successful.go.to.other.workflows'), "success"
                        );
                        window.location = Routing.generate('vipa_workflow_flow_active', {
                            journalId: journalId
                        });
                    }else{
                        alert('Some error occured');
                    }
                });
            });
        },
        finishWorkflow: function($this){
            swal({
                title: Translator.trans('workflow.are.you.sure.finish.workflow'),
                text: Translator.trans('workflow.finish.warnings'),
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: '#5fba7d',
                confirmButtonText: Translator.trans('workflow.yes.finish.workflow'),
                closeOnConfirm: false
            }, function() {
                $.get( Routing.generate('dp_workflow_finish_workflow', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder
                }), function( data ) {
                    if(data.success == true){
                        swal(
                            Translator.trans('successful'),
                            Translator.trans('successful.go.to.article.page'), "success"
                        );
                        window.location = data.data.redirectUrl;
                    }else{
                        alert('Some error occured');
                    }
                });
            });
        },
        sendComment: function ($this, $dialogId) {
            var $comment = $($this).parent().parent().find('.dialog-comment-input').val();
            if($comment == ''){
                return true;
            }
            $.post(Routing.generate('dp_workflow_dialog_posts_new_comment', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId
            }), {
                comment: $comment
            }, function( data ) {
                if(data.success == true){
                    VipaWorkflow.loadPosts($dialogId);
                    $($this).parent().parent().find('.dialog-comment-input').val('');
                    swal(Translator.trans('excellent'), Translator.trans('your.messages.sended'), "success");
                }
            });
        },
        browseFiles: function ($this, $dialogId) {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_dialog_posts_browse_files', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder,
                    dialogId: $dialogId
                }),
                autoSize: false,
                width: '600px',
                maxWidth: '600px',
                height: 'auto'
            });
        },
        uploadCustomFile: function () {
            $('#workflow-file-upload-hidden-input').click();
        },
        exposeUploadedFile: function (data) {
            if(typeof data.result.files !== 'undefined'){
                swal(Translator.trans('wrong.file'), Translator.trans('select.another.file.type'), "warning");
                return false;
            }
            $('.upload-file-expose-template').clone().insertBefore('#upload-button-box');
            var realTemplate = $('.upload-file-expose-template:last');
            realTemplate.removeClass('hidden').removeClass('upload-file-expose-template');
            realTemplate.find('input[type="checkbox"]').attr('data-file-original-name', data.result.originalname);
            realTemplate.find('input[type="checkbox"]').attr('data-file-name', data.result.filename);
            realTemplate.find('th:eq(1)').html(data.result.originalname);
            realTemplate.find('th:eq(2) a').attr('href', data.result.filepath);
        },
        uploadReviewVersionFile: function (data) {
            if(typeof data.result.files !== 'undefined'){
                swal(Translator.trans('wrong.file'), Translator.trans('select.another.file.type'), "warning");
                return false;
            }
            $.post(Routing.generate('vipa_workflow_article_detail_upload_review_version_file', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder
            }), {file: data.result}, function( data ) {
                if(data.success == true){
                    VipaWorkflow.showSubmissionDetail();
                }
            });
        },
        sendSelectedFiles: function($dialogId){
            var files = [];
            var findCheckedFileInputs = $('#browse-files-table').find('input:checked:not([data-file-name=""])');
            $.each(findCheckedFileInputs, function (index, value) {
                files.push({
                    fileOriginalName: $(value).attr('data-file-original-name'),
                    fileName: $(value).attr('data-file-name')
                });
            });
            $.post(Routing.generate('dp_workflow_dialog_posts_new_file', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId
            }), {
                files: files
            }, function( data ) {
                if(data.success == true){
                    $.fancybox.close();
                    VipaWorkflow.loadPosts($dialogId);
                    swal(Translator.trans('excellent'), Translator.trans('your.files.sended'), "success");
                }
            });
        },
        browseReviewForms: function ($this, $dialogId) {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_dialog_posts_browse_review_forms', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder,
                    dialogId: $dialogId
                }),
                autoSize: false,
                width: '600px',
                maxWidth: '600px',
                height: 'auto'
            });
        },
        sendSelectedReviewForms: function($dialogId){
            var reviewForms = [];
            var findCheckedReviewFormInputs = $('#browse-review-forms-table').find('input:checked');
            $.each(findCheckedReviewFormInputs, function (index, value) {
                reviewForms.push($(value).val());
            });
            $.post(Routing.generate('dp_workflow_dialog_posts_new_review_form', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId
            }), {
                reviewForms: reviewForms
            }, function( data ) {
                if(data.success == true){
                    $.fancybox.close();
                    VipaWorkflow.loadPosts($dialogId);
                    swal(Translator.trans('excellent'), Translator.trans('your.review.forms.sended'), "success");
                }
            });
        },
        browseReviewFormResponses: function ($this, $dialogId) {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_dialog_posts_browse_review_form_responses', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder,
                    dialogId: $dialogId
                }),
                autoSize: false,
                width: '600px',
                maxWidth: '600px',
                height: 'auto'
            });
        },
        sendSelectedReviewFormResponses: function($dialogId){
            var reviewFormResponses = [];
            var findCheckedReviewFormInputs = $('#browse-review-form-responses-table').find('input:checked');
            $.each(findCheckedReviewFormInputs, function (index, value) {
                reviewFormResponses.push($(value).val());
            });
            $.post(Routing.generate('dp_workflow_dialog_posts_new_review_form_response_preview', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId
            }), {
                reviewFormResponses: reviewFormResponses
            }, function( data ) {
                if(data.success == true){
                    $.fancybox.close();
                    VipaWorkflow.loadPosts($dialogId);
                    swal(Translator.trans('excellent'), Translator.trans('your.review.form.responses.sended'), "success");
                }
            });
        },
        browseReviewerUsers: function ($this, $dialogId) {
            var browseUrl = Routing.generate('vipa_workflow_reviewers_browse', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder
            });
            browseWindow = window.open(browseUrl);
            browseWindow.focus();
        },
        browseSectionEditorUsers: function ($this, $dialogId) {
            var browseUrl = Routing.generate('vipa_workflow_section_editors_browse', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder
            });
            browseWindow = window.open(browseUrl);
            browseWindow.focus();
        },
        addUserToUsersDialog: function ($id, $text) {
            $(".select2entity")
                .append('<option value="'+$id+'" selected="selected">'+$text+'</option>')
                .trigger('change')
                .select2entity()
            ;
        },
        addUserToUsersDialogViaButton: function (button, $id, $text) {
            $button = $(button);
            $button.html(Translator.trans('added.successfully')).attr('disabled', 'disabled');
            VipaWorkflow.addUserToUsersDialog($id, $text);
        },
        createReviewerUser: function(){
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('vipa_workflow_create_reviewer_user', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder
                }),
                autoSize: false,
                width: '600px',
                maxWidth: '600px',
                height: 'auto'
            });
        },
        postReviewerUser: function ($this) {
            var dialogForm = $('form[name="reviewer_user"]');
            $.post( dialogForm.attr('action'), dialogForm.serialize(), function( data ) {
                $.fancybox({
                    content: data,
                    type: 'inline',
                    autoSize: false,
                    width: '600px',
                    maxWidth: '600px',
                    height: 'auto'
                });
            });
        },
        addReviewerUser: function(){
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('vipa_workflow_add_reviewer_user', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder
                }),
                autoSize: false,
                width: '600px',
                maxWidth: '600px',
                height: 'auto'
            });
        },
        postAddReviewerUser: function ($this) {
            var dialogForm = $('form[name="add_reviewer_user"]');
            $.post( dialogForm.attr('action'), dialogForm.serialize(), function( data ) {
                $.fancybox({
                    content: data,
                    type: 'inline',
                    autoSize: false,
                    width: '600px',
                    maxWidth: '600px',
                    height: 'auto'
                });
            });
        },
        syncStepReviewForms: function ($this, $dialogId) {
            $.get(Routing.generate('dp_workflow_sync_step_review_forms', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder
            }), function( data ) {
                if(data.success == true){
                    swal(Translator.trans('excellent'), Translator.trans('review.forms.sync.successful'), "success");
                    VipaWorkflow.browseReviewForms($this, $dialogId);
                }
            });
        },
        /**
         * @link https://gist.github.com/behram/e38ffbe820b4419a270249d7893ec3e7
         */
        normalizeReviewSubmissionFom: function(){
            $('#form-render-div-wrap input').each(function(){
                $(this).attr('value',$(this).val());
            });
            $('#form-render-div-wrap input[type="checkbox"]').each(function(){
                if(this.checked){
                    $(this).attr('checked', 'checked');
                }else{
                    $(this).removeAttr('checked');
                }
            });
            $('#form-render-div-wrap input[type="radio"]').each(function(){
                if(this.checked){
                    $(this).attr('checked', 'checked');
                }else{
                    $(this).removeAttr('checked');
                }
            });
            $('#form-render-div-wrap textarea').each(function(){
                var $textareaVal = $(this).val();
                $(this).val($textareaVal).attr('value', $textareaVal).html($textareaVal);
            });
            $('#form-render-div-wrap select').each(function(){
                $(this).find('option:selected').attr('selected', 'selected');
                $(this).find('option:not(:selected)').removeAttr('selected');
            });
        },
        submitReviewForm: function($dialogId, $id){
            VipaWorkflow.normalizeReviewSubmissionFom();
            var formContents = $('#form-render-div-wrap').clone().html();
            $.post(Routing.generate('dp_workflow_dialog_posts_submit_review_form', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId,
                id: $id
            }), {
                formContent: formContents
            }, function( data ) {
                if(data.success == true){
                    swal({
                        title: Translator.trans('excellent'),
                        text: Translator.trans('your.submitted.review.form'),
                        type: "success",
                        showCancelButton: false,
                        confirmButtonColor: "#46b8da",
                        confirmButtonText: Translator.trans('ok'),
                        closeOnConfirm: false
                    }, function() {
                        window.close();
                    });
                }
            });
        },
        inviteReviewer: function($dialogId){
            $.get(Routing.generate('dp_workflow_dialog_invite_reviewer', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId
            }), function( data ) {
                if(data.success == true){
                    VipaWorkflow.loadStep(stepOrder);
                    swal(Translator.trans('excellent'), Translator.trans('reviewer.invitation.mail.sended'), "success");
                }
            });
        },
        remindReviewer: function($dialogId){
            $.get(Routing.generate('dp_workflow_dialog_remind_reviewer', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId
            }), function( data ) {
                if(data.success == true){
                    VipaWorkflow.loadStep(stepOrder);
                    swal(Translator.trans('excellent'), Translator.trans('reviewer.remind.mail.sended'), "success");
                }
            });
        },
        acceptReviewRequest: function($dialogId){
            $.get(Routing.generate('dp_workflow_dialog_accept_review', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId
            }), function( data ) {
                if(data.success == true){
                    VipaWorkflow.loadStep(stepOrder);
                    swal(Translator.trans('excellent'), Translator.trans('you.accepted.review'), "success");
                }
            });
        },
        rejectReviewRequest: function($dialogId){
            $.get(Routing.generate('dp_workflow_dialog_reject_review', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId
            }), function( data ) {
                if(data.success == true){
                    window.location = data.redirectUrl;
                    swal(Translator.trans('success'), Translator.trans('you.rejected'), "warning");
                }
            });
        },
        editArticleMetadata: function($this) {
            $this = $($this);
            $.fancybox({
                type: 'ajax',
                autoSize: false,
                width: '600px',
                maxWidth: '600px',
                height: 'auto',
                href: $this.attr('href')
            });
        },
        updateArticleMetadata: function () {
            var articleMetadataForm = $('form[name="article_metadata_edit"]');
            $.post( articleMetadataForm.attr('action'), articleMetadataForm.serialize(), function( data ) {
                $.fancybox(data);
            });
        }
    };
});