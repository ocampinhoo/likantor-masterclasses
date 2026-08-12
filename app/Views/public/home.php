<?php
$landingUrl = url('/masterclass/revision-estructuras-post-sismo');
?>

<!-- Hero -->
<section class="hero-home" aria-labelledby="hero-title">
    <div class="hero-home__pattern" aria-hidden="true"></div>
    <div class="container hero-home__grid">
        <div class="hero-home__content">
            <p class="section__eyebrow">Guadalajara, Jalisco · México</p>
            <h1 id="hero-title" class="hero-home__title">
                Ingeniería estructural con <em>rigor técnico</em>
            </h1>
            <p class="hero-home__lead">
                LIKANTOR — Likantor Ingeniería en Estructuras — es una firma dedicada al diseño,
                análisis y construcción de estructuras de concreto y acero.
            </p>
            <div class="hero-home__actions">
                <a href="<?= e($landingUrl) ?>" class="btn btn--primary btn--lg">Ver Masterclass</a>
                <a href="#quienes-somos" class="btn btn--secondary">Conocer Likantor</a>
            </div>
        </div>
        <div class="hero-home__visual" aria-hidden="true">
            <svg class="hero-home__structure" viewBox="0 0 320 240" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Estructura arquitectónica estilizada">
                <rect x="40" y="180" width="240" height="8" fill="#c9a227" opacity="0.6"/>
                <rect x="60" y="80" width="12" height="100" fill="#94a3b8"/>
                <rect x="248" y="80" width="12" height="100" fill="#94a3b8"/>
                <rect x="60" y="80" width="200" height="10" fill="#c9a227"/>
                <rect x="60" y="120" width="200" height="8" fill="#64748b" opacity="0.5"/>
                <rect x="60" y="155" width="200" height="8" fill="#64748b" opacity="0.5"/>
                <path d="M36 80 L160 24 L284 80" stroke="#c9a227" stroke-width="3" fill="none"/>
                <line x1="160" y1="24" x2="160" y2="80" stroke="#94a3b8" stroke-width="2" stroke-dasharray="4 4"/>
            </svg>
        </div>
    </div>
</section>

<!-- Quiénes somos -->
<section class="section" id="quienes-somos" aria-labelledby="about-title">
    <div class="container">
        <div class="grid-2 grid-2--aside">
            <div>
                <span class="section__eyebrow">Quiénes somos</span>
                <h2 id="about-title" class="section__title">Likantor Ingeniería en Estructuras</h2>
                <p class="section__lead">
                    Somos una firma con base en Guadalajara, Jalisco, México, orientada al diseño
                    y análisis estructural para proyectos de construcción.
                </p>
                <p>
                    Según su presencia pública oficial, Likantor combina conocimiento técnico en
                    estructuras de concreto y acero con contenido educativo sobre construcción,
                    compartido a través de sus canales digitales y comunidad en línea.
                </p>
                <div class="social-links">
                    <a href="https://linktr.ee/likantor.estructuras" target="_blank" rel="noopener noreferrer">Redes oficiales (Linktree)</a>
                </div>
            </div>
            <div class="about-stats" aria-label="Datos verificables">
                <div class="stat-item">
                    <span class="stat-item__value">GDL</span>
                    <span class="stat-item__label">Guadalajara, Jal.</span>
                </div>
                <div class="stat-item">
                    <span class="stat-item__value">MX</span>
                    <span class="stat-item__label">México</span>
                </div>
                <div class="stat-item">
                    <span class="stat-item__value">C+A</span>
                    <span class="stat-item__label">Concreto y acero</span>
                </div>
                <div class="stat-item">
                    <span class="stat-item__value">Edu</span>
                    <span class="stat-item__label">Contenido formativo</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Qué hacemos -->
<section class="section section--muted" id="que-hacemos" aria-labelledby="services-title">
    <div class="container">
        <span class="section__eyebrow">Qué hacemos</span>
        <h2 id="services-title" class="section__title">Servicios de ingeniería estructural</h2>
        <p class="section__lead">
            Acompañamos proyectos desde el cálculo estructural hasta la materialización en obra,
            con enfoque en precisión técnica y criterio de ingeniería.
        </p>
        <div class="grid-3">
            <article class="card">
                <div class="card__icon" aria-hidden="true">∑</div>
                <h3 class="card__title">Cálculo estructural</h3>
                <p class="card__text">Análisis y dimensionamiento de elementos estructurales conforme a criterios técnicos aplicables.</p>
            </article>
            <article class="card">
                <div class="card__icon" aria-hidden="true">◫</div>
                <h3 class="card__title">Diseño estructural</h3>
                <p class="card__text">Desarrollo de soluciones estructurales para proyectos de concreto y acero.</p>
            </article>
            <article class="card">
                <div class="card__icon" aria-hidden="true">▣</div>
                <h3 class="card__title">Construcción</h3>
                <p class="card__text">Respaldo técnico en la ejecución de estructuras, desde el concepto hasta la obra.</p>
            </article>
        </div>
    </div>
</section>

<!-- Áreas de especialidad -->
<section class="section" id="especialidad" aria-labelledby="specialty-title">
    <div class="container">
        <span class="section__eyebrow">Áreas de especialidad</span>
        <h2 id="specialty-title" class="section__title">Estructuras de concreto y acero</h2>
        <div class="specialty-grid">
            <article class="card">
                <h3 class="card__title">Concreto armado</h3>
                <p class="card__text">Diseño y detallado de elementos de concreto para edificaciones y obras civiles.</p>
            </article>
            <article class="card">
                <h3 class="card__title">Estructuras de acero</h3>
                <p class="card__text">Soluciones en marcos y elementos metálicos para proyectos industriales y comerciales.</p>
            </article>
            <article class="card">
                <h3 class="card__title">Análisis estructural</h3>
                <p class="card__text">Evaluación de comportamiento estructural bajo cargas gravitacionales y laterales.</p>
            </article>
            <article class="card">
                <h3 class="card__title">Revisión post-evento</h3>
                <p class="card__text">Criterios técnicos para evaluación visual y conceptual después de eventos sísmicos. <em>(formación educativa)</em></p>
            </article>
            <article class="card">
                <h3 class="card__title">Documentación técnica</h3>
                <p class="card__text">Planos estructurales, memorias de cálculo y especificaciones de diseño.</p>
            </article>
            <article class="card">
                <h3 class="card__title">Asesoría en obra</h3>
                <p class="card__text">Seguimiento y congruencia entre diseño estructural y ejecución en sitio.</p>
            </article>
        </div>
    </div>
</section>

<!-- Propuesta de valor -->
<section class="section section--dark" id="propuesta" aria-labelledby="value-title">
    <div class="container value-proposition">
        <div>
            <span class="section__eyebrow">Propuesta de valor</span>
            <h2 id="value-title" class="section__title">Precisión, criterio y formación continua</h2>
            <p class="section__lead">
                En Likantor entendemos que cada estructura soporta vidas y patrimonio.
                Nuestro enfoque combina rigor de cálculo con comunicación clara del criterio técnico.
            </p>
        </div>
        <ul class="check-list">
            <li>Diseño estructural fundamentado en análisis y normativa aplicable</li>
            <li>Comunicación técnica accesible para equipos de diseño y construcción</li>
            <li>Contenido educativo sobre construcción en canales digitales oficiales</li>
            <li>Masterclasses en línea para difundir conocimiento estructural</li>
        </ul>
    </div>
</section>

<!-- Fernando Robledo -->
<section class="section" id="instructor" aria-labelledby="fernando-title">
    <div class="container">
        <span class="section__eyebrow">Liderazgo técnico</span>
        <h2 id="fernando-title" class="section__title">Fernando Robledo</h2>
        <div class="instructor-card">
            <div class="instructor-card__avatar" aria-hidden="true">FR</div>
            <div>
                <h3 class="instructor-card__name">Fernando Robledo</h3>
                <p class="instructor-card__role">Ponente · Likantor — Ingeniería en Estructuras</p>
                <p>
                    Fernando Robledo es el ponente de la Masterclass
                    <strong>Revisión de Estructuras Post-Sismo</strong>, impartida bajo Likantor
                    — Ingeniería en Estructuras.
                </p>
                <p class="placeholder">
                    [PLACEHOLDER — Biografía profesional, formación académica y trayectoria de
                    Fernando Robledo pendiente de confirmación y autorización por Likantor.]
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Contenido educativo -->
<section class="section section--muted" id="formacion" aria-labelledby="education-title">
    <div class="container">
        <div class="education-banner">
            <span class="section__eyebrow">Formación y autoridad</span>
            <h2 id="education-title" class="education-banner__title">Contenido educativo en construcción</h2>
            <p class="education-banner__text">
                Likantor comparte contenido sobre diseño, construcción y temas técnicos a través
                de sus redes sociales oficiales. Además, según su Linktree oficial, cuenta con
                un podcast sobre temas de construcción e industria.
            </p>
            <a href="https://linktr.ee/likantor.estructuras" class="btn btn--secondary" target="_blank" rel="noopener noreferrer">
                Ver canales oficiales
            </a>
        </div>
    </div>
</section>

<!-- CTA Masterclass -->
<section class="section cta-masterclass" id="masterclass" aria-labelledby="cta-mc-title">
    <div class="container cta-masterclass__inner">
        <div>
            <span class="section__eyebrow">Próximo evento</span>
            <h2 id="cta-mc-title" class="section__title">Masterclass: Revisión de Estructuras Post-Sismo</h2>
            <p class="section__lead" style="margin-bottom:0.5rem;">
                Aprende a identificar señales que ameritan atención después de un sismo y comprende
                qué aspectos debe revisar un profesional.
            </p>
            <p class="cta-masterclass__date">
                17 de septiembre de 2026 · 6:30 PM (Ciudad de México) · 3 horas · En vivo por Zoom · 65 USD
            </p>
        </div>
        <div>
            <a href="<?= e($landingUrl) ?>" class="btn btn--primary btn--lg">Quiero mi lugar</a>
        </div>
    </div>
</section>
