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
        showHistoryLog: function ($this) {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dergipark_workflow_history_log', {
                    journalId: journalId,
                    workflowId: workflowId
                })
            });
        },
        createSpecificDialog: function ($this, $dialogType) {
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_create_specific_dialog', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder
                })
            });
        },
        createBasicDialog: function($this){
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_create_basic_dialog', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder
                })
            });
        },
        acceptAndGotoArrangement: function($this){
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_accept_goto_arrangement', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder
                })
            });
        },
        gotoReviewing: function($this){
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_goto_reviewing', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder
                })
            });
        },
        acceptSubmission: function($this){
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_accept_submission', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder
                })
            });
        },
        declineSubmission: function($this){
            $.fancybox({
                type: 'ajax',
                href: Routing.generate('dp_workflow_decline_submission', {
                    journalId: journalId,
                    workflowId: workflowId,
                    stepOrder: stepOrder
                })
            });
        }
    };
});
