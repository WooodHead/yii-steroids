import React from 'react';
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import {reduxForm, SubmissionError, getFormValues} from 'redux-form';
import _isEqual from 'lodash-es/isEqual';
import _get from 'lodash-es/get';
import _set from 'lodash-es/set';
import _isUndefined from 'lodash-es/isUndefined';

import {http, view} from 'components';
import AutoSaveHelper from './AutoSaveHelper';

let valuesSelector = null;

@connect(
    (state, props) => {
        valuesSelector = valuesSelector || getFormValues(props.formId);

        return {
            form: props.formId,
            formValues: valuesSelector(state),
            formRegisteredFields: _get(state, `form.${props.formId}.registeredFields`),
        };
    }
)
@reduxForm()
export default class Form extends React.PureComponent {

    static propTypes = {
        formId: PropTypes.string.isRequired,
        prefix: PropTypes.string,
        model: PropTypes.oneOfType([
            PropTypes.string,
            PropTypes.func,
        ]),
        action: PropTypes.string,
        layout: PropTypes.oneOf(['default', 'inline', 'horizontal']),
        layoutCols: PropTypes.arrayOf(PropTypes.number),
        onSubmit: PropTypes.func,
        onAfterSubmit: PropTypes.func,
        onChange: PropTypes.func,
        onComplete: PropTypes.func,
        autoSave: PropTypes.bool,
        initialValues: PropTypes.object,
        className: PropTypes.string,
        view: PropTypes.func,
        formValues: PropTypes.object,
        formRegisteredFields: PropTypes.object,
    };

    static childContextTypes = {
        formId: PropTypes.string,
        prefix: PropTypes.string,
        model: PropTypes.oneOfType([
            PropTypes.string,
            PropTypes.func,
        ]),
        layout: PropTypes.oneOf(['default', 'inline', 'horizontal']),
        layoutCols: PropTypes.arrayOf(PropTypes.number),
    };

    constructor() {
        super(...arguments);

        this._onSubmit = this._onSubmit.bind(this);
    }

    getChildContext() {
        return {
            model: this.props.model,
            prefix: this.props.prefix,
            formId: this.props.formId,
            layout: this.props.layout,
            layoutCols: this.props.layoutCols,
        };
    }

    render() {
        const FormView = this.props.view || view.get('form.FormView');
        return (
            <FormView
                {...this.props}
                onSubmit={this.props.handleSubmit(this._onSubmit)}
            >
                {this.props.children}
            </FormView>
        );
    }

    componentWillMount() {
        // Restore values from query, when autoSave flag is set
        if (this.props.autoSave) {
            AutoSaveHelper.restore(this.props.formId, this.props.initialValues);
        }
    }

    componentWillReceiveProps(nextProps) {
        // Check update values for trigger event
        if ((this.props.onChange || this.props.autoSave) && !_isEqual(this.props.formValues, nextProps.formValues)) {
            if (this.props.onChange) {
                this.props.onChange(nextProps.formValues);
            }
            if (this.props.autoSave) {
                AutoSaveHelper.save(this.props.formId, nextProps.formValues);
            }
        }
    }

    _onSubmit(values) {
        // Append non touched fields to values object
        Object.keys(this.props.formRegisteredFields || {}).forEach(key => {
            const name = this.props.formRegisteredFields[key].name;
            if (_isUndefined(_get(values, name))) {
                _set(values, name, null);
            }
        });

        if (this.props.onSubmit) {
            return this.props.onSubmit(values);
        }

        return http.post(this.props.action || location.pathname, values)
            .then(response => {
                if (response.errors) {
                    throw new SubmissionError(response.errors);
                }
                if (this.props.autoSave) {
                    AutoSaveHelper.remove(this.props.formId);
                }
                if (this.props.onComplete) {
                    this.props.onComplete(values, response);
                }
            });
    }

}
