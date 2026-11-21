<style>
    .login-shell {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background:
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.16), transparent 34%),
            linear-gradient(135deg, #0d47a1 0%, #123d7a 48%, #0b1f3b 100%);
    }

    .login-panel {
        width: min(1040px, 100%);
        display: grid;
        grid-template-columns: 1.05fr 0.95fr;
        border-radius: 24px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 24px 70px rgba(4, 20, 54, 0.32);
    }

    .login-hero {
        color: #fff;
        padding: 42px;
        background:
            linear-gradient(180deg, rgba(11, 31, 59, 0.12), rgba(11, 31, 59, 0.42)),
            linear-gradient(135deg, #123d7a 0%, #0d47a1 52%, #1a5cc8 100%);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 24px;
        position: relative;
    }

    .brand-mark {
        display: inline-flex;
        align-items: center;
        gap: 14px;
    }

    .brand-mark img {
        width: 54px;
        height: 54px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.16);
        padding: 8px;
        object-fit: contain;
    }

    .brand-copy h1 {
        margin: 0;
        font-size: 34px;
        font-weight: 700;
        line-height: 1.1;
    }

    .brand-copy p {
        margin: 14px 0 0;
        max-width: 34ch;
        font-size: 15px;
        line-height: 1.7;
        color: rgba(255, 255, 255, 0.9);
    }

    .hero-points {
        display: grid;
        gap: 12px;
        margin-top: auto;
    }

    .hero-point {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 14px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.11);
        backdrop-filter: blur(8px);
    }

    .hero-point i {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: rgba(255, 255, 255, 0.16);
    }

    .hero-point span {
        font-size: 14px;
        line-height: 1.4;
    }

    .login-form-area {
        padding: 42px 38px;
        background: #fff;
    }

    .login-form-head h2 {
        margin: 0;
        color: #0f172a;
        font-size: 28px;
        font-weight: 700;
    }

    .login-form-head p {
        margin: 10px 0 0;
        color: #64748b;
        line-height: 1.6;
    }

    .login-field {
        margin-top: 18px;
    }

    .login-field .input-group {
        width: 100%;
    }

    .login-field .input-group-addon {
        min-width: 46px;
        background: #f8fafc;
        border-color: #dbe5f1;
        color: #0d47a1;
        border-radius: 12px 0 0 12px;
    }

    .login-field .form-control {
        height: 48px;
        border-color: #dbe5f1;
        box-shadow: none;
        border-radius: 0 12px 12px 0;
        color: #0f172a;
    }

    .login-field .form-control:focus {
        border-color: #0d47a1;
        box-shadow: 0 0 0 3px rgba(13, 71, 161, 0.12);
    }

    .login-submit {
        margin-top: 24px;
    }

    .login-submit .btn {
        width: 100%;
        height: 50px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #0d47a1 0%, #1a5cc8 100%);
        color: #fff;
        font-weight: 700;
        box-shadow: 0 14px 24px rgba(13, 71, 161, 0.22);
    }

    .login-submit .btn:hover,
    .login-submit .btn:focus {
        color: #fff;
        background: linear-gradient(135deg, #0b3d8b 0%, #164ea9 100%);
    }

    .login-error {
        display: block;
        margin-top: 6px;
        color: #dc2626;
        font-size: 13px;
    }

    .login-success {
        margin-top: 20px;
        padding: 13px 15px;
        border: 1px solid #bce7ce;
        border-radius: 12px;
        background: #effaf4;
        color: #17603b;
        font-size: 14px;
        line-height: 1.55;
    }

    .login-back-link {
        display: inline-block;
        margin-top: 22px;
        color: #0d47a1;
        font-weight: 600;
        text-decoration: none;
    }

    @media (max-width: 991px) {
        .login-panel {
            grid-template-columns: 1fr;
        }

        .login-hero,
        .login-form-area {
            padding: 30px 22px;
        }
    }
</style>
