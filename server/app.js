const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');
const mysql = require('mysql2/promise');
const cors = require('cors');
const util = require('util');
const express = require('express');
const session = require('express-session');
const helmet = require('helmet');
const app = express();
app.use(cors());
app.use(express.json())
const JWT_SECRET = '9800338'; 


app.use(
  helmet.contentSecurityPolicy({
    directives: {
      defaultSrc: ["'self'"],
      styleSrc: ["'self'", 'https://fonts.googleapis.com'],
      fontSrc: ["'self'", 'https://fonts.gstatic.com'],
      // Agrega otras directivas y fuentes según sea necesario
    },
  })
);

const pool = mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'swap',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});



app.post('/singnin', async (req, res) => {
    const { email, password } = req.body;

    try {
        // Usando pool.query permite que el pool maneje la conexión por ti
        const [results] = await pool.query('SELECT * FROM usuarios WHERE email = ?', [email]);

        if (results.length > 0 && await bcrypt.compare(password, results[0].contraseña)) {
            // Asumiendo que 'id' es el nombre del campo de identificación del usuario en tu tabla
            const token = jwt.sign({ userId: results[0].id }, JWT_SECRET, { expiresIn: '1h' });
            res.send({ token });
        } else {
            // Mensaje genérico para no revelar si el correo existe o no
            res.status(401).send({ error: "Las credenciales proporcionadas son incorrectas" });
        }
    } catch (error) {
        console.error(error);
        res.status(500).send({ error: "Ocurrió un error al procesar la solicitud" });
    }
});


app.post('/registro', async (req, res) => {
    let conexion;
    try {
        conexion = await pool.getConnection(); // Obtiene una conexión del pool.
        await conexion.beginTransaction(); // Inicia una transacción.

        const { nombre, apellidoPaterno, apellidoMaterno, nTelefono, email, password, rol, empresa, rfc } = req.body;
        const hashedPassword = await bcrypt.hash(password, 10);

        // Inserta en la tabla usuarios y obtiene el insertId
        const [resultadoUsuario] = await conexion.query(
            'INSERT INTO usuarios (nombre, apellidoPaterno, apellidoMaterno, telefono, email, contraseña, rol) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [nombre, apellidoPaterno, apellidoMaterno, nTelefono, email, hashedPassword, rol]
        );
        const idUsuario = resultadoUsuario.insertId;  
        console.log(idUsuario);

        // Inserta en la tabla contratante si el rol es 'contratante'
        if (rol === 'contratante') {
            await conexion.query(
                "INSERT INTO contratante (idUsuario, empresa, rfc) VALUES (?, ?, ?)", 
                [idUsuario, empresa, rfc]
            );
        }

        // Completa la transacción
        await conexion.commit();
        res.status(201).send({ message: "Usuario registrado exitosamente" });
    } catch (error) {
        // Revierte la transacción en caso de error
        if (conexion) await conexion.rollback();
        console.error(error);
        res.status(500).send({ error: "Error al registrar el usuario" });
    } finally {
        // Libera la conexión de vuelta al pool
        if (conexion) conexion.release();
    }
});


const PORT = 3001;
app.listen(PORT, () => {
    console.log(`Servidor corriendo en http://localhost:${PORT}`);
});
