import Model from 'yii-steroids/frontend/base/Model';

import {locale} from 'components';

export default class ModelEntityMeta extends Model {

    static className = 'steroids\\modules\\gii\\forms\\ModelEntity';

    static fields() {
        return {
            'moduleId': {
                'component': 'InputField',
                'attribute': 'moduleId',
                'label': locale.t('Module ID'),
                'required': true
            },
            'name': {
                'component': 'InputField',
                'attribute': 'name',
                'label': locale.t('Class name'),
                'required': true
            },
            'tableName': {
                'component': 'InputField',
                'attribute': 'tableName',
                'label': locale.t('Table name'),
                'required': true
            },
            'migrateMode': {
                'component': 'CheckboxField',
                'attribute': 'migrateMode',
                'label': locale.t('Migrate mode'),
            }
        };
    }

}
