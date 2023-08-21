const GuacamoleLite = require("guacamole-lite");

const websocketOptions = {
  port: 8080,
};

const guacdOptions = {
  host: "guacd",
  port: 4822,
};

const clientOptions = {
  log: {
    level: 30,
  },

  crypt: {
    cypher: "AES-256-CBC",
    key: process.env.SECRETKEY,
  },
  allowedUnencryptedConnectionSettings: {
    rdp: ["width", "height", "dpi", "GUAC_AUDIO"],
  },
};

const callbacks = {
  processConnectionSettings: function (settings, callback) {
    if (settings.expiration < Date.now()) {
      console.error("Token expired");

      return callback(new Error("Token expired"));
    }

    settings.connection["drive-path"] = "/tmp/guacamole_" + settings.userId;

    console.info(settings);

    callback(null, settings);
  },
};

const guacServer = new GuacamoleLite(
  websocketOptions,
  guacdOptions,
  clientOptions,
  callbacks
);
