import configureMockStore from 'redux-mock-store'
import thunk from 'redux-thunk'
import Actions from '../app/Resources/js/actions/home'
import Types from '../app/Resources/js/constants/home'

const middlewares = [thunk]
const mockStore = configureMockStore(middlewares)

global.fetch = jest.fn().mockImplementation(() => {
    return new Promise((resolve, reject) => {
        resolve({
            ok: true,
            Id: '123',
            json: function () {
                return {timelineJson: {key: 'value'}}
            }
        })
    })
})

describe('fetchSingleDate()', () => {
    it('test dispatch() value', () => {
        const username = 'foo'
        const date = '2017-03-30'
        const store = mockStore()

        return store.dispatch(Actions.fetchSingleDate(username, date))
            .then(() => {
                const [prevFetch, afterFetch] = store.getActions()

                expect(prevFetch.type).toBe(Types.AJAX_FETCH_START)
            })
    })
})
