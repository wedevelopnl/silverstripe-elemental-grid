// Entwine bridge first — registers jQuery hooks before DOM matching triggers
import '../bridge/entwine';

// Boot system — registers components with Injector on DOMContentLoaded
import '../boot';

// Styles — extracted by Vite into a separate CSS bundle
import '../styles/bundle.scss';
