<!DOCTYPE html><html lang="en"><meta charset="utf-8"></head><body><div style="background:#f7f7f7"><div style="width:757px;margin:0 auto;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;color:#666;line-height:1.4em"><table cellspacing="0" cellpadding="0" border="0" style="margin-bottom:10px;background:#ffffff;width:700px"><tbody><tr><td width="33%" valign="middle" style="text-align:right;padding:20px 0 10px 0"><a target="_blank" href="http://www.icpna.edu.pe"><img border="0" width="700" height="35" style="width:700px;min-height:72px" alt="ICPNA" src="http://vlearning.icpna.edu.pe/in/web/bundles/applicationsubscriber/images/header.jpg"></a></td></tr><tr><td style="text-align:center;"><img src="http://vlearning.icpna.edu.pe/in/web/bundles/applicationsubscriber/images/aprobado.jpg" width="700" height="279" style="min-height:279px;"></td></tr><tr><td><img src="http://vlearning.icpna.edu.pe/in/web/bundles/applicationsubscriber/images/header-vlearning.jpg"/></td></tr><tr><td><table width="627" border="0" cellpadding="10" cellspacing="0" style="border:0;padding:0;margin:0;"><tr><td style="font-size:25px; font-family:Arial; color:#003d88; text-align=left;"><p style="margin:0 0 0 2.3em;font-size:16px;">Hola, <b>{{ student.firstname }}</b></p>
<p style="font-size:16px; margin-left:3.6em;">Has culminado tu aprendizaje de inglés correspondiente al <b>{{ _c.title }}.</b></p>
{% if exercise_result_message %}
    <p style="font-size:16px; margin-left:3.6em;">Tu calificación final de <b>{{ score }}</b> (aprobado) </p><p style="font-size:16px; margin-left:3.6em;">¡Felicidades! Gracias por vivir la experiencia 
{% else %}
    <p style="font-size:16px; margin-left:3.6em;">Tu calificación final de <b>{{ score }}</b>  (desaprobado) </p><p style="font-size:16px; margin-left:3.6em;">No te desanimes, te invitamos a seguir viviendo la experiencia 
{% endif %}
<p style="font-size:16px; margin-left:3.6em;">Ingresa a tus cursos: {{ modules_path }}</p>
<span style="font-weight:bold; font-size:16px; font-style:italic; font-family:Arial; color:#4d7fbf;">V Learning</span></p><br><p style="font-size:16px; margin-left:3.6em;">Saludos</p><p style="font-size:16px; margin-left:3.6em;">ICPNA</p>
</td></tr></table></td></tr><tr><td><img src="http://vlearning.icpna.edu.pe/in/web/bundles/applicationsubscriber/images/footer.jpg"/></td></tr></tbody></table></div>
</div>
</body>
</html>
