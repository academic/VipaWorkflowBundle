$(document).ready(function() {
    OjsWorkflow = {
        basicJournalWfSetting: function($this) {
            $this = $($this);
            $.fancybox({
                type: 'ajax',
                href: $this.attr('href')
            });
        },
        stepGrantedUsersSetup: function($this, $stepOrder) {
            $.fancybox( '<h1>Lorem lipsum</h1>' );
        },
        showStepDetail: function($this, $stepOrder) {
            $.fancybox(Translator.trans('Confirm'));
        },
        updateJournalWfSetting: function () {
            var journalWfSettingForm = $('form[name="journal_wf_setting"]');
            $.post( journalWfSettingForm.attr('action'), journalWfSettingForm.serialize(), function( data ) {
                $.fancybox(data);
            });
        }
    };
});