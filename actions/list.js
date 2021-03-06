import _get from 'lodash-es/get';

import {http} from 'components';

export const LIST_INIT = 'LIST_INIT';
export const LIST_BEFORE_FETCH = 'LIST_BEFORE_FETCH';
export const LIST_AFTER_FETCH = 'LIST_AFTER_FETCH';
export const LIST_ITEM_UPDATE = 'LIST_ITEM_UPDATE';
export const LIST_DESTROY = 'LIST_DESTROY';
export const LIST_TOGGLE_ITEM = 'LIST_TOGGLE_ITEM';
export const LIST_TOGGLE_ALL = 'LIST_TOGGLE_ALL';

const lazyTimers = {};

export const init = (listId, props) => dispatch => dispatch({
    action: props.action || props.action === '' ? props.action : null,
    page: 1,
    pageSize: props.defaultPageSize,
    sort: props.defaultSort || null,
    total: props.total || null,
    query: props.query || null,
    items: props.items || null,
    loadMore: props.loadMore,
    primaryKey: props.primaryKey,
    listId,
    type: LIST_INIT,
});

export const fetch = (listId, params) => (dispatch, getState) => {
    const list = {
        ..._get(getState(), ['list', listId]),
        ...params,
    };
    if (!list.action && list.action !== '') {
        return;
    }

    return dispatch([
        {
            ...params,
            listId,
            type: LIST_BEFORE_FETCH,
        },
        http.post(list.action || location.pathname, {
            ...list.query,
            page: list.page,
            pageSize: list.pageSize,
            sort: list.sort,
        })
            .then(result => ({
                ...result,
                listId,
                type: LIST_AFTER_FETCH,
            })),
    ]);
};

export const lazyFetch = (listId, params) => dispatch => {
    if (lazyTimers[listId]) {
        clearTimeout(lazyTimers[listId]);
    }
    lazyTimers[listId] = setTimeout(() => dispatch(fetch(listId, params)), 200);
};

export const setPage = (listId, page) => fetch(listId, {
    page,
});

export const setPageSize = (listId, pageSize) => fetch(listId, {
    page: 1,
    pageSize,
});

export const setSort = (listId, sort) => fetch(listId, {
    sort,
});

export const refresh = listId => fetch(listId);

export const update = (listId, item, condition) => ({
    item,
    condition,
    listId,
    type: LIST_ITEM_UPDATE,
});

export const destroy = listId => ({
    listId,
    type: LIST_DESTROY,
});

export const toggleItem = (listId, itemId) => ({
    listId,
    itemId,
    type: LIST_TOGGLE_ITEM,
});

export const toggleAll = listId => ({
    listId,
    type: LIST_TOGGLE_ALL,
});
