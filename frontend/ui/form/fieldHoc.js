import React from 'react';
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import {Field, formValueSelector, change} from 'redux-form';
import _get from 'lodash-es/get';
import _upperFirst from 'lodash-es/upperFirst';

import FieldLayout from './FieldLayout';

const defaultConfig = {
    attributes: [''],
};
const selectors = {};

@connect(
    (state, props) => {
        if (!props.formId) {
            return {};
        }

        // Lazy create selector
        if (!selectors[props.formId]) {
            selectors[props.formId] = formValueSelector(props.formId);
        }
        const selector = selectors[props.formId];

        // Fetch values
        const values = {};
        props._config.attributes.map(attribute => {
            values['formValue' + _upperFirst(attribute)] = selector(state, FieldHoc.getName(props, attribute));
        });
        return values;
    }
)
class FieldHoc extends React.PureComponent {

    static propTypes = {
        attribute: PropTypes.string,
    };

    static getName(props, attribute) {
        const name = attribute ? props['attribute' + _upperFirst(attribute)] : props.attribute;
        return (props.prefix || '') + name;
    }

    constructor() {
        super(...arguments);

        // Check attributes is set
        if (this.props.formId) {
            this.props._config.attributes.forEach(attribute => {
                if (!this.props['attribute' + _upperFirst(attribute)]) {
                    throw new Error(`Please set attribute names for component "${this.props._wrappedComponent.name}" in form "${this.props.formId}"`);
                }
            });
        }

        if (!this.props.formId) {
            this.state = {
                value: _get(this.props, 'input.value'),
            };
        }
    }

    render() {
        const {_wrappedComponent, _config, ...props} = this.props;
        const WrappedComponent = _wrappedComponent;

        const inputProps = {};
        _config.attributes.forEach(attribute => {
            inputProps[`input${_upperFirst(attribute)}`] = {
                name: FieldHoc.getName(this.props, attribute),
                value: this._getValue(attribute),
                onChange: value => this._setValue(attribute, value),
            };
        });

        return (
            <FieldLayout {...props}>
                {this.props.formId && _config.attributes.map(attribute => (
                    <Field
                        key={this.props.formId + attribute}
                        name={FieldHoc.getName(this.props, attribute)}
                        component='input'
                        type='hidden'
                    />
                ))}
                <WrappedComponent
                    {...props}
                    {...inputProps}
                    formId={this.props.formId}
                />
            </FieldLayout>
        );
    }

    _getValue(attribute) {
        if (this.props.formId) {
            return _get(this.props, 'formValue' + _upperFirst(attribute));
        } else {
            return attribute
                ? _get(this.state.value, attribute)
                : this.state.value;
        }
    }

    _setValue(attribute, value) {
        if (this.props.formId) {
            this.props.dispatch(change(this.props.formId, FieldHoc.getName(this.props, attribute), value));
        } else {
            this.setState({
                value: attribute
                    ? {
                        ...this.state.value,
                        [attribute]: value,
                    }
                    : value,
            });
        }
    }
}

export default (config = defaultConfig) => WrappedComponent => class FieldHocWrapper extends React.PureComponent {

    static WrappedComponent = WrappedComponent;

    static contextTypes = {
        formId: PropTypes.string,
        model: PropTypes.oneOfType([
            PropTypes.string,
            PropTypes.func,
        ]),
        prefix: PropTypes.string,
        layout: PropTypes.string,
        layoutCols: PropTypes.arrayOf(PropTypes.number),
    };

    render() {
        return (
            <FieldHoc
                {...this.props}
                formId={this.props.formId || this.context.formId}
                model={this.props.model || this.context.model}
                prefix={this.props.prefix || this.context.prefix}
                layout={this.props.layout || this.context.layout}
                layoutCols={this.props.layoutCols || this.context.layoutCols}
                _wrappedComponent={WrappedComponent}
                _config={config}
            />
        );
    }

};