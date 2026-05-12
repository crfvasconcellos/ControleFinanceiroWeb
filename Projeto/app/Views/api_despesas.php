<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>API Despesas</title>
</head>
<body>
    <h2>Testar API</h2>
    <input type="text" id="key" placeholder="Cole sua API key..." size="60">
    <button onclick="testar()">Enviar</button>
    <br><br>
    <pre id="resultado"></pre>

    <script>
    async function testar() {
        const key = document.getElementById('key').value.trim();
        const res = await fetch('/?route=api_despesas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'api_key=' + encodeURIComponent(key)
        });
        document.getElementById('resultado').textContent = await res.text();
    }
    </script>
</body>
</html>