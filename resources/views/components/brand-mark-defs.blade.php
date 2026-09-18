<svg width="0" height="0" style="position:absolute">
    <defs>
        <radialGradient id="planetBody" cx="35%" cy="28%" r="78%">
            <stop offset="0%" stop-color="#235347"/>
            <stop offset="55%" stop-color="#163832"/>
            <stop offset="100%" stop-color="#051F20"/>
        </radialGradient>
        <radialGradient id="spotSwirl" cx="38%" cy="34%" r="78%">
            <stop offset="0%" stop-color="#DAF1DE"/>
            <stop offset="55%" stop-color="#8EB69B"/>
            <stop offset="100%" stop-color="#235347"/>
        </radialGradient>
        <radialGradient id="sphereShade" cx="34%" cy="26%" r="80%">
            <stop offset="0%" stop-color="#DAF1DE" stop-opacity="0.22"/>
            <stop offset="45%" stop-color="#DAF1DE" stop-opacity="0"/>
            <stop offset="82%" stop-color="#051F20" stop-opacity="0"/>
            <stop offset="100%" stop-color="#020908" stop-opacity="0.5"/>
        </radialGradient>
        <radialGradient id="moonGlow" cx="38%" cy="34%" r="72%">
            <stop offset="0%" stop-color="#DAF1DE"/>
            <stop offset="100%" stop-color="#8EB69B"/>
        </radialGradient>
        <clipPath id="planetClip"><circle cx="320" cy="320" r="120"/></clipPath>
        <filter id="softBlur" x="-80%" y="-80%" width="260%" height="260%"><feGaussianBlur stdDeviation="4.5"/></filter>
    </defs>

    <symbol id="jupyterMark" viewBox="0 0 640 640">
        <g clip-path="url(#planetClip)">
            <circle cx="320" cy="320" r="120" fill="url(#planetBody)"/>
            <g opacity="0.85">
                <rect x="180" y="200" width="280" height="26" fill="#051F20" opacity="0.55" transform="rotate(-2 320 320)"/>
                <rect x="180" y="228" width="280" height="24" fill="#235347" opacity="0.6" transform="rotate(1.5 320 320)"/>
                <rect x="180" y="254" width="280" height="28" fill="#0B2B26" opacity="0.7" transform="rotate(-1 320 320)"/>
                <rect x="180" y="284" width="280" height="25" fill="#235347" opacity="0.5" transform="rotate(2 320 320)"/>
                <rect x="180" y="311" width="280" height="30" fill="#163832" opacity="0.65" transform="rotate(-1.5 320 320)"/>
                <rect x="180" y="343" width="280" height="24" fill="#0B2B26" opacity="0.6" transform="rotate(1 320 320)"/>
                <rect x="180" y="369" width="280" height="27" fill="#235347" opacity="0.55" transform="rotate(-2 320 320)"/>
                <rect x="180" y="398" width="280" height="23" fill="#051F20" opacity="0.5" transform="rotate(1.5 320 320)"/>
                <rect x="180" y="423" width="280" height="24" fill="#0B2B26" opacity="0.55" transform="rotate(-1 320 320)"/>
            </g>
            <g class="gaze">
                <circle cx="320" cy="320" r="30" fill="url(#spotSwirl)"/>
                <circle cx="320" cy="320" r="30" fill="none" stroke="#051F20" stroke-opacity="0.3" stroke-width="2"/>
            </g>
            <circle cx="320" cy="320" r="120" fill="url(#sphereShade)"/>
        </g>
        <circle cx="320" cy="320" r="120" fill="none" stroke="currentColor" stroke-opacity="0.3" stroke-width="2.5"/>
        <g transform="translate(320,320)">
            <circle class="moon moon-a" r="17" fill="url(#moonGlow)" opacity="0.3" filter="url(#softBlur)"/>
            <circle class="moon moon-a" r="10" fill="url(#moonGlow)" stroke="#051F20" stroke-opacity="0.35" stroke-width="1.6"/>
        </g>
        <g transform="translate(320,320)">
            <circle class="moon moon-b" r="14" fill="url(#moonGlow)" opacity="0.28" filter="url(#softBlur)"/>
            <circle class="moon moon-b" r="8" fill="url(#moonGlow)" stroke="#051F20" stroke-opacity="0.35" stroke-width="1.4"/>
        </g>
        <g transform="translate(320,320)">
            <circle class="moon moon-c" r="11" fill="url(#moonGlow)" opacity="0.26" filter="url(#softBlur)"/>
            <circle class="moon moon-c" r="6.5" fill="url(#moonGlow)" stroke="#051F20" stroke-opacity="0.35" stroke-width="1.2"/>
        </g>
    </symbol>

    <symbol id="jupyterMarkFlat" viewBox="0 0 640 640">
        <circle cx="320" cy="320" r="120" fill="none" stroke="currentColor" stroke-width="14"/>
        <circle cx="366" cy="366" r="22" fill="currentColor"/>
    </symbol>
</svg>

<style>
    .gaze { animation: gaze 15s cubic-bezier(0.62, 0, 0.18, 1) infinite; transform-box: fill-box; transform-origin: center; }
    @keyframes gaze {
        0%, 6% { transform: translate(0px, 0px); }
        18%, 26% { transform: translate(-34px, -9px); }
        40%, 48% { transform: translate(27px, 16px); }
        62%, 70% { transform: translate(7px, -29px); }
        84%, 90% { transform: translate(-18px, 20px); }
        100% { transform: translate(0px, 0px); }
    }
    .moon { transform-box: fill-box; transform-origin: center; }
    .moon-a { animation: moonOrbitA 9s linear infinite; }
    .moon-b { animation: moonOrbitB 14s linear infinite; }
    .moon-c { animation: moonOrbitC 20s linear infinite; }
    @keyframes moonOrbitA {
        0% { transform: translate(133.34px, -48.53px); }
        2.083% { transform: translate(140.02px, -24.62px); }
        4.167% { transform: translate(142.96px, -0.05px); }
        6.25% { transform: translate(142.14px, 24.53px); }
        8.333% { transform: translate(137.69px, 48.48px); }
        10.417% { transform: translate(129.83px, 71.24px); }
        12.5% { transform: translate(118.91px, 92.32px); }
        14.583% { transform: translate(105.31px, 111.33px); }
        16.667% { transform: translate(89.45px, 127.96px); }
        18.75% { transform: translate(71.79px, 142.01px); }
        20.833% { transform: translate(52.75px, 153.35px); }
        22.917% { transform: translate(32.76px, 161.93px); }
        25% { transform: translate(12.2px, 167.75px); }
        27.083% { transform: translate(-8.55px, 170.86px); }
        29.167% { transform: translate(-29.17px, 171.34px); }
        31.25% { transform: translate(-49.37px, 169.32px); }
        33.333% { transform: translate(-68.88px, 164.92px); }
        35.417% { transform: translate(-87.47px, 158.29px); }
        37.5% { transform: translate(-104.91px, 149.61px); }
        39.583% { transform: translate(-121.04px, 139.04px); }
        41.667% { transform: translate(-135.68px, 126.77px); }
        43.75% { transform: translate(-148.69px, 112.97px); }
        45.833% { transform: translate(-159.94px, 97.83px); }
        47.917% { transform: translate(-169.33px, 81.56px); }
        50% { transform: translate(-176.76px, 64.33px); }
        52.083% { transform: translate(-182.14px, 46.37px); }
        54.167% { transform: translate(-185.41px, 27.87px); }
        56.25% { transform: translate(-186.52px, 9.04px); }
        58.333% { transform: translate(-185.42px, -9.9px); }
        60.417% { transform: translate(-182.1px, -28.71px); }
        62.5% { transform: translate(-176.54px, -47.17px); }
        64.583% { transform: translate(-168.75px, -65.04px); }
        66.667% { transform: translate(-158.77px, -82.06px); }
        68.75% { transform: translate(-146.66px, -97.97px); }
        70.833% { transform: translate(-132.49px, -112.5px); }
        72.917% { transform: translate(-116.37px, -125.39px); }
        75% { transform: translate(-98.48px, -136.35px); }
        77.083% { transform: translate(-78.99px, -145.1px); }
        79.167% { transform: translate(-58.16px, -151.38px); }
        81.25% { transform: translate(-36.29px, -154.93px); }
        83.333% { transform: translate(-13.73px, -155.52px); }
        85.417% { transform: translate(9.11px, -152.97px); }
        87.5% { transform: translate(31.75px, -147.16px); }
        89.583% { transform: translate(53.66px, -138.03px); }
        91.667% { transform: translate(74.31px, -125.64px); }
        93.75% { transform: translate(93.12px, -110.16px); }
        95.833% { transform: translate(109.54px, -91.86px); }
        97.917% { transform: translate(123.09px, -71.14px); }
        100% { transform: translate(133.34px, -48.53px); }
    }
    @keyframes moonOrbitB {
        0% { transform: translate(152.33px, 40.82px); }
        2.083% { transform: translate(142.45px, 68.59px); }
        4.167% { transform: translate(128.35px, 94.33px); }
        6.25% { transform: translate(110.53px, 117.34px); }
        8.333% { transform: translate(89.61px, 137.07px); }
        10.417% { transform: translate(66.29px, 153.15px); }
        12.5% { transform: translate(41.3px, 165.37px); }
        14.583% { transform: translate(15.33px, 173.68px); }
        16.667% { transform: translate(-11px, 178.14px); }
        18.75% { transform: translate(-37.1px, 178.92px); }
        20.833% { transform: translate(-62.5px, 176.26px); }
        22.917% { transform: translate(-86.77px, 170.44px); }
        25% { transform: translate(-109.6px, 161.76px); }
        27.083% { transform: translate(-130.7px, 150.52px); }
        29.167% { transform: translate(-149.87px, 137.05px); }
        31.25% { transform: translate(-166.94px, 121.66px); }
        33.333% { transform: translate(-181.79px, 104.65px); }
        35.417% { transform: translate(-194.32px, 86.3px); }
        37.5% { transform: translate(-204.48px, 66.9px); }
        39.583% { transform: translate(-212.23px, 46.7px); }
        41.667% { transform: translate(-217.54px, 25.97px); }
        43.75% { transform: translate(-220.43px, 4.95px); }
        45.833% { transform: translate(-220.9px, -16.13px); }
        47.917% { transform: translate(-218.99px, -37.03px); }
        50% { transform: translate(-214.73px, -57.54px); }
        52.083% { transform: translate(-208.17px, -77.43px); }
        54.167% { transform: translate(-199.37px, -96.49px); }
        56.25% { transform: translate(-188.43px, -114.5px); }
        58.333% { transform: translate(-175.41px, -131.26px); }
        60.417% { transform: translate(-160.44px, -146.56px); }
        62.5% { transform: translate(-143.64px, -160.17px); }
        64.583% { transform: translate(-125.13px, -171.9px); }
        66.667% { transform: translate(-105.11px, -181.52px); }
        68.75% { transform: translate(-83.74px, -188.83px); }
        70.833% { transform: translate(-61.26px, -193.63px); }
        72.917% { transform: translate(-37.93px, -195.71px); }
        75% { transform: translate(-14.04px, -194.88px); }
        77.083% { transform: translate(10.07px, -190.99px); }
        79.167% { transform: translate(34.01px, -183.9px); }
        81.25% { transform: translate(57.33px, -173.5px); }
        83.333% { transform: translate(79.54px, -159.77px); }
        85.417% { transform: translate(100.11px, -142.75px); }
        87.5% { transform: translate(118.45px, -122.56px); }
        89.583% { transform: translate(133.99px, -99.49px); }
        91.667% { transform: translate(146.14px, -73.9px); }
        93.75% { transform: translate(154.39px, -46.36px); }
        95.833% { transform: translate(158.32px, -17.52px); }
        97.917% { transform: translate(157.66px, 11.83px); }
        100% { transform: translate(152.33px, 40.82px); }
    }
    @keyframes moonOrbitC {
        0% { transform: translate(127.82px, -115.09px); }
        2.083% { transform: translate(148.63px, -87.7px); }
        4.167% { transform: translate(164.53px, -57.41px); }
        6.25% { transform: translate(175.17px, -25.28px); }
        8.333% { transform: translate(180.44px, 7.64px); }
        10.417% { transform: translate(180.52px, 40.34px); }
        12.5% { transform: translate(175.74px, 71.98px); }
        14.583% { transform: translate(166.61px, 101.84px); }
        16.667% { transform: translate(153.66px, 129.38px); }
        18.75% { transform: translate(137.49px, 154.21px); }
        20.833% { transform: translate(118.66px, 176.08px); }
        22.917% { transform: translate(97.71px, 194.82px); }
        25% { transform: translate(75.16px, 210.36px); }
        27.083% { transform: translate(51.45px, 222.69px); }
        29.167% { transform: translate(27px, 231.85px); }
        31.25% { transform: translate(2.19px, 237.9px); }
        33.333% { transform: translate(-22.65px, 240.95px); }
        35.417% { transform: translate(-47.22px, 241.1px); }
        37.5% { transform: translate(-71.25px, 238.49px); }
        39.583% { transform: translate(-94.5px, 233.24px); }
        41.667% { transform: translate(-116.74px, 225.5px); }
        43.75% { transform: translate(-137.76px, 215.42px); }
        45.833% { transform: translate(-157.39px, 203.14px); }
        47.917% { transform: translate(-175.43px, 188.83px); }
        50% { transform: translate(-191.73px, 172.64px); }
        52.083% { transform: translate(-206.13px, 154.73px); }
        54.167% { transform: translate(-218.48px, 135.29px); }
        56.25% { transform: translate(-228.64px, 114.49px); }
        58.333% { transform: translate(-236.47px, 92.53px); }
        60.417% { transform: translate(-241.84px, 69.6px); }
        62.5% { transform: translate(-244.63px, 45.93px); }
        64.583% { transform: translate(-244.72px, 21.76px); }
        66.667% { transform: translate(-242px, -2.66px); }
        68.75% { transform: translate(-236.37px, -27.05px); }
        70.833% { transform: translate(-227.75px, -51.09px); }
        72.917% { transform: translate(-216.09px, -74.44px); }
        75% { transform: translate(-201.35px, -96.73px); }
        77.083% { transform: translate(-183.54px, -117.54px); }
        79.167% { transform: translate(-162.71px, -136.41px); }
        81.25% { transform: translate(-139px, -152.86px); }
        83.333% { transform: translate(-112.61px, -166.35px); }
        85.417% { transform: translate(-83.86px, -176.34px); }
        87.5% { transform: translate(-53.21px, -182.3px); }
        89.583% { transform: translate(-21.25px, -183.74px); }
        91.667% { transform: translate(11.27px, -180.25px); }
        93.75% { transform: translate(43.45px, -171.56px); }
        95.833% { transform: translate(74.3px, -157.63px); }
        97.917% { transform: translate(102.76px, -138.65px); }
        100% { transform: translate(127.82px, -115.09px); }
    }
    @media (prefers-reduced-motion: reduce) {
        .gaze, .moon { animation-play-state: paused !important; }
    }
</style>
