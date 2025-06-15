const slogans = ['Tu aliado en la gestión del tiempo', 'Manage your time with ease'];

const getRandomInt = max => {
    return Math.floor(Math.random() * Math.floor(max));
};

export default () => {
    return slogans[getRandomInt(slogans.length)];
};
