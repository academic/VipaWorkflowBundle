$(document).ready(function() {
    $('.fancybox').fancybox({});
    setInterval(function(){ OjsWorkflow.refreshAgoPlugin()}, 5000);
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
        loadStep: function ($stepOrder) {
            $.get(Routing.generate('dergipark_workflow_timeline_step', {
                journalId: journalId,
                workflowId: workflowId,
                stepOrder: $stepOrder
            }), function( data ) {
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
        showHistoryLog: function ($this) {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dergipark_workflow_history_log', {
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
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_accept_submission', {
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
                    swal(Translator.trans('excellent'), Translator.trans('your.messages.sended'), "success");
                }
            });
        }
    };
});
