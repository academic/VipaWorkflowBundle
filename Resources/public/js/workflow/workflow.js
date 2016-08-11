$(document).ready(function() {
    $('.fancybox').fancybox({});
    OjsWorkflow = {
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
                href: Routing.generate('dergipark_workflow_step_users_setup', {journalId: journalId, stepOrder: $stepOrder}),
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
        showStepDetail: function($this, $stepOrder) {
            $.fancybox({
                type: 'inline',
                href: '#wf-step-description-'+$stepOrder
            });
        },
        showSubmissionDetail: function () {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dergipark_workflow_article_detail', {
                    journalId: journalId,
                    workflowId: workflowId
                })
            });
        },
        loadStep: function ($stepOrder) {
            $.get(Routing.generate('dergipark_workflow_timeline_step', {
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
        finishAction: function ($dialogId) {
            $.get(Routing.generate('dp_workflow_dialog_finish', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: stepOrder,
                dialogId: $dialogId
            }), function( data ) {
                if(data.success == true){
                    OjsWorkflow.loadDialogs();
                    swal(
                        Translator.trans('successful'),
                        Translator.trans('successful.finished.action'), "success"
                    );
                }
            });
        },
        showHistoryLog: function ($this) {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dergipark_workflow_history_log', {
                    journalId: journalId,
                    workflowId: workflowId
                })
            });
        },
        showPermissionTable: function ($this) {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dergipark_workflow_permission_table', {
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
                        OjsWorkflow.loadStep(3);
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
                        OjsWorkflow.loadStep(2);
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
                        window.location = Routing.generate('dergipark_workflow_flow_active', {
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
                    OjsWorkflow.loadPosts($dialogId);
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
                    OjsWorkflow.loadPosts($dialogId);
                    swal(Translator.trans('excellent'), Translator.trans('your.files.sended'), "success");
                }
            });
        }
    };
});
