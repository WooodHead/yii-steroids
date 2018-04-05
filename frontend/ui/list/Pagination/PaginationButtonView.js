import React from 'react';
import PropTypes from 'prop-types';

import {html} from 'components';

const bem = html.bem('PaginationButtonView');

export default class PaginationButtonView extends React.Component {

    static propTypes = {
        pages: PropTypes.arrayOf(PropTypes.shape({
            page: PropTypes.number,
            label: PropTypes.node,
            isActive: PropTypes.bool,
        })),
        onSelect: PropTypes.func,
    };

    render() {
        return (
            <ul className={bem(bem.block(), 'pagination')}>
                {this.props.pages.map((item, index) => (
                    <li
                        key={index}
                        className={bem(
                            bem.element('page', {hidden: !item.page}),
                            item.isActive ? 'active' : ''
                        )}
                    >
                        <a
                            className={bem.element('page-link', {hidden: !item.page})}
                            href='javascript:void(0)'
                            onClick={() => this.props.onSelect(item.page)}
                        >
                            {item.label}
                        </a>
                    </li>
                ))}
            </ul>
        );
    }

}