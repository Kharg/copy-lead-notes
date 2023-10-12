define('copy-lead-notes:views/admin/settings', ['views/settings/record/edit'], function (Dep) {

    return Dep.extend({

        gridLayoutType: 'record',

        events: {
            'click button[data-action="save"]': function () {
                this.actionSave();
            },
            'click button[data-action="cancel"]': function () {
                this.cancel();
            },
            'click button[data-action="resetToDefault"]': function () {
                this.confirm(this.translate('confirmation', 'messages'), () => {
                    this.resetToDefault();
                });
            },
        },

        buttonList: [
            {
                name: 'save',
                label: 'Save',
                style: 'primary',
                title: 'Ctrl+Enter',
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
            {
                name: 'resetToDefault',
                label: 'Restore',
            }
        ],

        detailLayout: [
            {
                "rows": [
                    [{"name": "copyLeadNotesForAccount"}, {"name": "copyLeadNotesForContact"}, {"name": "copyLeadNotesForOpportunity"}],
                ],
                "style": "default",
                "label": "Copy Lead Notes Settings"
            }
        ],

        setup: function () {
            Dep.prototype.setup.call(this);
        },

        afterSave: function () {
            Dep.prototype.afterSave.call(this);
            },

        resetToDefault: function () {
            Espo.Ajax
            .putRequest('Settings/1', {
                copyLeadNotesForAccount: this.getMetadata().get(['entityDefs', this.scope, 'fields', 'copyLeadNotesForAccount', 'default']),
                copyLeadNotesForContact: this.getMetadata().get(['entityDefs', this.scope, 'fields', 'copyLeadNotesForContact', 'default']),
                copyLeadNotesForOpportunity: this.getMetadata().get(['entityDefs', this.scope, 'fields', 'copyLeadNotesForOpportunity', 'default']),
            })
            .then(response => {
                this.model.fetch();
                window.location.reload();
            });
        },

    });
});
