var Heimdall = (function () {
    function Heimdall() {
    }
    Heimdall.hashCode = function (str) {
        var hash = 0, i, chr;
        if (str.length === 0)
            return null;
        for (i = 0; i < str.length; i++) {
            chr = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + chr;
            hash |= 0;
        }
        return Math.abs(hash).toString();
    };
    Heimdall.compareVersions = function (a_components, b_components) {
        if (a_components === b_components) {
            return 0;
        }
        var partsNumberA = a_components.split(".");
        var partsNumberB = b_components.split(".");
        for (var i = 0; i < partsNumberA.length; i++) {
            var valueA = parseInt(partsNumberA[i]);
            var valueB = parseInt(partsNumberB[i]);
            if (valueA > valueB || isNaN(valueB)) {
                return 1;
            }
            if (valueA < valueB) {
                return -1;
            }
        }
    };
    Heimdall.isObject = function (o) {
        return (typeof o === "object" || typeof o === 'function') && (o !== null);
    };
    Heimdall.mergeRecursive = function () {
        var _this = this;
        var objects = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            objects[_i] = arguments[_i];
        }
        return objects.reduce(function (prev, obj) {
            Object.keys(obj).forEach(function (key) {
                var pVal = prev[key];
                var oVal = obj[key];
                if (Array.isArray(pVal) && Array.isArray(oVal)) {
                    prev[key] = pVal.concat.apply(pVal, oVal);
                }
                else if (_this.isObject(pVal) && _this.isObject(oVal)) {
                    prev[key] = _this.merge(pVal, oVal);
                }
                else {
                    prev[key] = oVal;
                }
            });
            return prev;
        }, {});
    };
    Heimdall.merge = function () {
        var objects = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            objects[_i] = arguments[_i];
        }
        var r = {};
        objects.forEach(function (obj) {
            for (var o in obj) {
                r[o] = obj[o];
            }
        });
        return r;
    };
    Heimdall.writeToLocalStorage = function (name, data, exp) {
        if (exp === void 0) { exp = 0; }
        var key = this._cachePrefix + this.hashCode(name);
        var value = (data === null || data === void 0 ? void 0 : data.data) ? data : { data: data };
        if (HeimdallData === null || HeimdallData === void 0 ? void 0 : HeimdallData.version)
            value = this.merge(value, { version: HeimdallData.version });
        if (exp > 0)
            value = this.merge(value, { exp: Date.now() + exp });
        localStorage.setItem(key, JSON.stringify(value));
    };
    Heimdall.readFromLocalStorage = function (name) {
        var key = this._cachePrefix + this.hashCode(name);
        var data = JSON.parse(localStorage.getItem(key)) || {};
        if ((data === null || data === void 0 ? void 0 : data.exp) && (data === null || data === void 0 ? void 0 : data.exp) < Date.now()) {
            localStorage.removeItem(key);
            return {};
        }
        if ((data === null || data === void 0 ? void 0 : data.version) && this.compareVersions(data.version, HeimdallData.version) !== 0) {
            localStorage.removeItem(key);
            return {};
        }
        return data;
    };
    Heimdall.updateLocalStorage = function (name, data, exp, recursive) {
        if (exp === void 0) { exp = 0; }
        if (recursive === void 0) { recursive = false; }
        var old = this.readFromLocalStorage(name);
        var value = (data === null || data === void 0 ? void 0 : data.data) ? data : { data: data };
        this.writeToLocalStorage(name, recursive ? this.merge(old, value) : this.mergeRecursive(old, value), exp);
    };
    Heimdall._cachePrefix = "WP_HEIMDALL_";
    return Heimdall;
}());
