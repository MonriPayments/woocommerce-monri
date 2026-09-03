let settings = {};

const getSetting = jest.fn((name, defaultValue = null) => {
    if (Object.prototype.hasOwnProperty.call(settings, name)) {
        return settings[name];
    }
    return defaultValue;
});

const __setSettings = (newSettings) => {
    settings = { ...newSettings };
};

const __resetSettings = () => {
    settings = {};
    getSetting.mockClear();
};

module.exports = {
    getSetting,
    __setSettings,
    __resetSettings,
};
