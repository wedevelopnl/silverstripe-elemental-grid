import { render, screen } from '@testing-library/react';

describe('Smoke tests', () => {
  it('has a working jsdom environment', () => {
    expect(document).toBeDefined();
    expect(window).toBeDefined();
    expect(document.createElement('div')).toBeInstanceOf(HTMLDivElement);
  });

  it('compiles TypeScript', () => {
    const message: string = 'TypeScript works';
    const count: number = 42;
    expect(message).toBe('TypeScript works');
    expect(count).toBe(42);
  });

  it('renders a React component', () => {
    function Greeting({ name }: { name: string }) {
      return <h1>Hello, {name}!</h1>;
    }

    render(<Greeting name="World" />);
    expect(screen.getByRole('heading').textContent).toBe('Hello, World!');
  });
});
