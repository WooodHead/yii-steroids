import React from 'react';
import PropTypes from 'prop-types';

import {html} from 'components';
import './FieldLayoutView.scss';
const bem = html.bem('FieldLayoutView');

export default class FieldLayoutView extends React.PureComponent {

    static propTypes = {
        label: PropTypes.oneOfType([
            PropTypes.string,
            PropTypes.bool,
        ]),
        hint: PropTypes.oneOfType([
            PropTypes.string,
            PropTypes.bool,
        ]),
        errors: PropTypes.oneOfType([
            PropTypes.string,
            PropTypes.arrayOf(PropTypes.string),
        ]),
        required: PropTypes.bool,
        layout: PropTypes.oneOf(['default', 'inline', 'horizontal']),
        layoutProps: PropTypes.object,
        size: PropTypes.oneOf(['sm', 'md', 'lg']),
        className: PropTypes.string,
    };

    render() {
        return (
            <div className={bem(
                bem.block({
                    layout: this.props.layout
                }),
                'form-group',
                this.props.layout === 'horizontal' && 'row',
                this.props.layout === 'inline' && 'form-inline'
            )}>
                {this.props.label && (
                    <label className={bem(
                        bem.element('label', {
                            required: this.props.required
                        }),
                        this.props.layout === 'horizontal' && 'col-form-label text-right',
                        this.props.layout === 'horizontal' && 'col-' + this.props.layoutProps.cols[0],
                        this.props.layout === 'inline' && 'sr-only',
                    )}>
                        {this.props.label + ':'}
                    </label>
                )}
                <div
                    className={bem(
                        bem.element('field'),
                        this.props.layout === 'horizontal' && 'col-' + this.props.layoutProps.cols[1],
                        this.props.layout === 'horizontal' && !this.props.label && 'offset-' + this.props.layoutProps.cols[0]
                    )}
                >
                    {this.props.children}
                    {this.props.errors && (
                        <div className={bem(bem.element('invalid-feedback'), 'invalid-feedback')}>
                            {[].concat(this.props.errors).map((error, index) => (
                                <div key={index}>
                                    {error}
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        );
    }
}
