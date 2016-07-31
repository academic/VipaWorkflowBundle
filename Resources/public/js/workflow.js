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
        }
    };
});
