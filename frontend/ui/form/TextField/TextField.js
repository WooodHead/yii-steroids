import React from 'react';
import PropTypes from 'prop-types';
import {submit} from 'redux-form';

import {view} from 'components';
import fieldHoc from '../fieldHoc';

@fieldHoc()
export default class TextField extends React.PureComponent {

    static propTypes = {
        label: PropTypes.string,
        hint: PropTypes.string,
        attribute: PropTypes.string,
        input: PropTypes.shape({
            name: PropTypes.string,
            value: PropTypes.any,
            onChange: PropTypes.func,
        }),
        required: PropTypes.bool,
        size: PropTypes.oneOf(['sm', 'md', 'lg']),
        placeholder: PropTypes.string,
        disabled: PropTypes.bool,
        submitOnEnter: PropTypes.bool,
        inputProps: PropTypes.object,
        onChange: PropTypes.func,
        className: PropTypes.string,
        view: PropTypes.func,
    };

    static defaultProps = {
        size: 'md',
        disabled: false,
        required: false,
        className: '',
        placeholder: '',
        submitOnEnter: false,
    };

    constructor() {
        super(...arguments);

        this._onKeyUp = this._onKeyUp.bind(this);
    }

    render() {
        const TextFieldView = this.props.view || view.get('form.TextFieldView');
        return (
            <TextFieldView
                {...this.props}
                inputProps={{
                    name: this.props.input.name,
                    value: this.props.input.value || '',
                    onChange: e => this.props.input.onChange(e.target.value),
                    onKeyUp: this._onKeyUp,
                    placeholder: this.props.placeholder,
                    disabled: this.props.disabled,
                    ...this.props.inputProps,
                }}
            />
        );
    }

    _onKeyUp(e) {
        if (this.props.submitOnEnter && this.props.formId && e.keyCode === 13 && !e.shiftKey) {
            e.preventDefault();

            // TODO This is not worked in redux... =(
            this.props.dispatch(submit(this.props.formId));
        }
    }

}
