import Monri from '../../blocks/monri';

describe('blocks/monri.js', () => {
    it('exports window.Monri', () => {
        expect(Monri).toBe(window.Monri);
        expect(typeof Monri).toBe('function');
    });
});
