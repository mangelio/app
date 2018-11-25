module.exports = {
    env: {
        browser: true,
        es6: true,
        node: true
    },
    extends: ["eslint:recommended", "standard"],
    parserOptions: {
        ecmaVersion: 2018,
        sourceType: "module"
    },
    rules: {
        "semi": [
            "error",
            "always"
        ],
        "no-console": [
            "error",
            "always"
        ]
    }
};