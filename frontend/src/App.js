import { useState, useEffect } from "react";

export default function TodoList() {
  const [todos, setTodos] = useState([]);
  const [input, setInput] = useState("");

  // API endpoint (adjust if your server runs on a different host/port)
  const API_URL =
    process.env.REACT_APP_API_URL ||
    "http://localhost/basictodolistforwebdev/backend/insert.php";

  // Load saved todos on mount
  useEffect(() => {
    try {
      const saved = localStorage.getItem("todos");
      if (saved) setTodos(JSON.parse(saved));
    } catch (e) {
      console.error("Failed to load todos:", e);
    }
  }, []);

  // Save todos whenever they change
  useEffect(() => {
    try {
      localStorage.setItem("todos", JSON.stringify(todos));
    } catch (e) {
      console.error("Failed to save todos:", e);
    }
  }, [todos]);

  // Create a client fallback id
  const makeClientId = () =>
    Date.now().toString(36) + "-" + Math.random().toString(36).slice(2, 9);

  // Add a new todo (sends to server, falls back to client-only if server fails)
  const addTodo = async (text) => {
    const t = (text ?? input).trim();
    if (t === "") return;
    // Optimistically prepare a local item (use client id until server responds)
    const clientId = makeClientId();
    const newTodo = { id: clientId, text: t, completed: false };
    setTodos((prev) => [...prev, newTodo]);
    setInput("");

    // Send to server
    try {
      const res = await fetch(API_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ text: t }),
      });
      const data = await res.json();
      if (res.ok && data?.status === "success" && data.id) {
        // Replace client id with server id
        setTodos((prev) =>
          prev.map((td) => (td.id === clientId ? { ...td, id: data.id } : td))
        );
      } else {
        console.warn("Server returned an error or unexpected response", data);
        // keep client id and state; user can retry or accept local only
      }
    } catch (err) {
      console.error("Failed to send todo to server:", err);
      // network error: keep local client-side todo
    }
  };

  // Form submit handler (handles Enter and Add button)
  const handleAddTodo = (e) => {
    if (e && e.preventDefault) e.preventDefault();
    addTodo();
  };

  // Delete a todo
  const deleteTodo = (id) => {
    setTodos(todos.filter((todo) => todo.id !== id));
  };

  // Toggle complete
  const toggleComplete = (id) => {
    setTodos(
      todos.map((todo) =>
        todo.id === id ? { ...todo, completed: !todo.completed } : todo
      )
    );
  };

  return (
    <div style={styles.container}>
      <h1 style={styles.title}>📝 Todo List</h1>

      {/* Input Section */}
      <form onSubmit={handleAddTodo} style={styles.inputContainer}>
        <input
          type="text"
          value={input}
          onChange={(e) => setInput(e.target.value)}
          placeholder="Add a new task..."
          style={styles.input}
          aria-label="New todo"
        />
        <button type="submit" style={styles.addButton}>
          Add
        </button>
      </form>

      {/* Todo List */}
      <ul style={styles.list}>
        {todos.length === 0 && <p style={styles.empty}>No tasks yet!</p>}
        {todos.map((todo) => (
          <li key={todo.id} style={styles.listItem}>
            <span
              onClick={() => toggleComplete(todo.id)}
              style={{
                ...styles.todoText,
                textDecoration: todo.completed ? "line-through" : "none",
                color: todo.completed ? "#999" : "#333",
                cursor: "pointer",
              }}
            >
              {todo.completed ? "✅" : "⬜"} {todo.text}
            </span>
            <button
              onClick={() => deleteTodo(todo.id)}
              style={styles.deleteButton}
            >
              ❌
            </button>
          </li>
        ))}
      </ul>

      {/* Footer */}
      {todos.length > 0 && (
        <p style={styles.footer}>
          {todos.filter((t) => t.completed).length} / {todos.length} completed
        </p>
      )}
    </div>
  );
}

// Inline Styles
const styles = {
  container: {
    maxWidth: "500px",
    margin: "50px auto",
    padding: "20px",
    fontFamily: "Arial, sans-serif",
    backgroundColor: "#f9f9f9",
    borderRadius: "10px",
    boxShadow: "0 2px 10px rgba(0,0,0,0.1)",
  },
  title: {
    textAlign: "center",
    color: "#333",
  },
  inputContainer: {
    display: "flex",
    gap: "10px",
    marginBottom: "20px",
  },
  input: {
    flex: 1,
    padding: "10px",
    fontSize: "16px",
    border: "1px solid #ddd",
    borderRadius: "5px",
    outline: "none",
  },
  addButton: {
    padding: "10px 20px",
    fontSize: "16px",
    backgroundColor: "#4CAF50",
    color: "white",
    border: "none",
    borderRadius: "5px",
    cursor: "pointer",
  },
  list: {
    listStyle: "none",
    padding: 0,
  },
  listItem: {
    display: "flex",
    justifyContent: "space-between",
    alignItems: "center",
    padding: "10px",
    marginBottom: "8px",
    backgroundColor: "#fff",
    borderRadius: "5px",
    border: "1px solid #eee",
  },
  todoText: {
    fontSize: "16px",
    flex: 1,
  },
  deleteButton: {
    background: "none",
    border: "none",
    cursor: "pointer",
    fontSize: "18px",
  },
  empty: {
    textAlign: "center",
    color: "#999",
  },
  footer: {
    textAlign: "center",
    color: "#666",
    marginTop: "10px",
  },
};