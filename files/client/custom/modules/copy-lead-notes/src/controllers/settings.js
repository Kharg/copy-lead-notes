define('copy-lead-notes:controllers/settings', 'controllers/admin', function (Dep) {

    return Dep.extend({

        defaultAction: 'index',

        checkAccess: function () {
            if (this.getUser().isAdmin()) {
                return true;
            }

            return false;
        },

        index: function () {
            this.actionIndex();
        },

        actionIndex: function () {
            var model = this.getSettingsModel();

            model.once('sync', function () {
                model.id = '1';
                this.main('views/settings/edit', {
                    model: model,
                    headerTemplate: 'copy-lead-notes:views/admin/settings-header',
                    recordView: 'copy-lead-notes:views/admin/settings'
                });
            }, this);
            model.fetch();
        }
    });
});
