FROM node:alpine

WORKDIR /app

RUN npm install --save guacamole-lite

COPY app.js .

CMD ["node", "app.js"]