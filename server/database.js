const mysql = require('mysql2');

const coneccion = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'swap'
});

coneccion.connect((error) => {
    if (error) throw error;
    console.log('Conexión exitosa a la base de datos');
}); 


module.exports = coneccion;