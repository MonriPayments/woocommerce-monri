const __ = jest.fn((text, domain) => text);
const _x = jest.fn((text, context, domain) => text);
const _n = jest.fn((single, plural, number, domain) => (number === 1 ? single : plural));
const sprintf = jest.fn((format, ...args) => {
    let index = 0;
    return format.replace(/%s/g, () => args[index++]);
});

module.exports = {
    __,
    _x,
    _n,
    sprintf,
};
